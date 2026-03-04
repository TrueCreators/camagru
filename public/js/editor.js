/**
 * Editor functionality
 */

document.addEventListener('DOMContentLoaded', () => {
    const MAX_UPLOAD_SIZE = 5 * 1024 * 1024; // 5MB

    // Elements
    const webcamModeBtn = document.getElementById('webcam-mode-btn');
    const uploadModeBtn = document.getElementById('upload-mode-btn');
    const webcamSection = document.getElementById('webcam-section');
    const uploadSection = document.getElementById('upload-section');
    const videoElement = document.getElementById('webcam-video');
    const previewCanvas = document.getElementById('preview-canvas');
    const captureBtn = document.getElementById('capture-btn');
    const webcamError = document.getElementById('webcam-error');
    const fileInput = document.getElementById('file-input');
    const uploadPreview = document.getElementById('upload-preview');
    const uploadPreviewCanvas = document.getElementById('upload-preview-canvas');
    const uploadPlaceholder = document.getElementById('upload-placeholder');
    const uploadBtn = document.getElementById('upload-btn');
    const overlayOptions = document.querySelectorAll('.overlay-option');
    const myImagesContainer = document.getElementById('my-images-container');
    const statusMessage = document.getElementById('status-message');

    let webcam = null;
    let currentOverlay = '';
    let overlayImage = null;
    let uploadedFile = null;
    let previewInterval = null;

    function updateActionButtons() {
        const hasOverlay = Boolean(currentOverlay);
        const hasUploadFile = Boolean(uploadedFile);

        if (captureBtn) {
            captureBtn.disabled = !hasOverlay || !webcam || !webcam.isReady;
        }

        if (uploadBtn) {
            uploadBtn.disabled = !hasOverlay || !hasUploadFile;
        }
    }

    // Initialize webcam
    async function initWebcam() {
        if (!videoElement) return;

        webcam = new Webcam(videoElement);

        try {
            await webcam.start();
            webcamError.classList.add('hidden');
            startPreviewLoop();
            updateActionButtons();
        } catch (error) {
            console.error('Failed to start webcam:', error);
            webcamError.classList.remove('hidden');
            updateActionButtons();
        }
    }

    // Preview loop for live overlay
    function startPreviewLoop() {
        if (previewInterval) {
            cancelAnimationFrame(previewInterval);
        }

        function updatePreview() {
            if (webcam && webcam.isReady && previewCanvas) {
                const ctx = previewCanvas.getContext('2d');
                const { width, height } = webcam.getVideoSize();

                previewCanvas.width = width;
                previewCanvas.height = height;

                ctx.clearRect(0, 0, width, height);

                if (overlayImage && overlayImage.complete) {
                    ctx.drawImage(overlayImage, 0, 0, width, height);
                }
            }
            previewInterval = requestAnimationFrame(updatePreview);
        }

        updatePreview();
    }

    // Mode switching
    webcamModeBtn?.addEventListener('click', () => {
        webcamModeBtn.classList.remove('bg-gray-200', 'text-gray-700');
        webcamModeBtn.classList.add('bg-blue-600', 'text-white');
        uploadModeBtn.classList.remove('bg-blue-600', 'text-white');
        uploadModeBtn.classList.add('bg-gray-200', 'text-gray-700');

        webcamSection.classList.remove('hidden');
        uploadSection.classList.add('hidden');

        if (!webcam || !webcam.isReady) {
            initWebcam();
        }
    });

    uploadModeBtn?.addEventListener('click', () => {
        uploadModeBtn.classList.remove('bg-gray-200', 'text-gray-700');
        uploadModeBtn.classList.add('bg-blue-600', 'text-white');
        webcamModeBtn.classList.remove('bg-blue-600', 'text-white');
        webcamModeBtn.classList.add('bg-gray-200', 'text-gray-700');

        uploadSection.classList.remove('hidden');
        webcamSection.classList.add('hidden');

        if (webcam) {
            webcam.stop();
        }
        updateActionButtons();
    });

    // Overlay selection
    overlayOptions.forEach(option => {
        option.addEventListener('click', () => {
            // Update selection UI
            overlayOptions.forEach(opt => {
                opt.classList.remove('border-blue-500');
                opt.classList.add('border-transparent');
            });
            option.classList.remove('border-transparent');
            option.classList.add('border-blue-500');

            // Load overlay image
            currentOverlay = option.dataset.overlay;

            if (currentOverlay) {
                overlayImage = new Image();
                overlayImage.crossOrigin = 'anonymous';
                overlayImage.src = `/assets/overlays/${currentOverlay}`;
                overlayImage.onload = () => {
                    updateUploadPreview();
                };
            } else {
                overlayImage = null;
                updateUploadPreview();
            }

            updateActionButtons();
        });
    });

    // Capture photo
    captureBtn?.addEventListener('click', async () => {
        if (!webcam || !webcam.isReady) {
            showStatus('Webcam not ready', 'error');
            return;
        }

        captureBtn.disabled = true;
        showStatus('Capturing...', 'info');

        try {
            const canvas = document.createElement('canvas');
            const imageData = webcam.capture(canvas, overlayImage);

            // Send to server
            const response = await App.fetch('/api/editor/capture', {
                method: 'POST',
                body: {
                    image: imageData,
                    overlay: currentOverlay
                }
            });

            const result = await response.json();

            if (result.success) {
                showStatus('Photo saved!', 'success');
                addImageToGallery(result.image);
            } else {
                showStatus(result.error || 'Failed to save photo', 'error');
            }
        } catch (error) {
            console.error('Capture error:', error);
            showStatus('Failed to capture photo', 'error');
        } finally {
            captureBtn.disabled = false;
        }
    });

    // File upload handling
    fileInput?.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;

        if (!file.type.match(/^image\/(jpeg|png|gif)$/)) {
            uploadedFile = null;
            updateActionButtons();
            showStatus('Please select a valid image (JPEG, PNG, or GIF)', 'error');
            return;
        }

        if (file.size > MAX_UPLOAD_SIZE) {
            uploadedFile = null;
            fileInput.value = '';
            updateActionButtons();
            showStatus('File is too large. Maximum size is 5MB.', 'error');
            return;
        }

        uploadedFile = file;
        const reader = new FileReader();

        reader.onload = (e) => {
            uploadPreview.src = e.target.result;
            uploadPreview.classList.remove('hidden');
            uploadPlaceholder.classList.add('hidden');
            updateUploadPreview();
            updateActionButtons();
        };

        reader.readAsDataURL(file);
    });

    // Update upload preview with overlay
    function updateUploadPreview() {
        if (!uploadPreview || !uploadPreview.src || uploadPreview.classList.contains('hidden')) {
            return;
        }

        const ctx = uploadPreviewCanvas.getContext('2d');

        uploadPreview.onload = () => {
            uploadPreviewCanvas.width = uploadPreview.naturalWidth;
            uploadPreviewCanvas.height = uploadPreview.naturalHeight;

            ctx.clearRect(0, 0, uploadPreviewCanvas.width, uploadPreviewCanvas.height);

            if (overlayImage && overlayImage.complete) {
                ctx.drawImage(overlayImage, 0, 0, uploadPreviewCanvas.width, uploadPreviewCanvas.height);
            }

            uploadPreviewCanvas.classList.remove('hidden');
        };

        if (uploadPreview.complete) {
            uploadPreviewCanvas.width = uploadPreview.naturalWidth;
            uploadPreviewCanvas.height = uploadPreview.naturalHeight;

            ctx.clearRect(0, 0, uploadPreviewCanvas.width, uploadPreviewCanvas.height);

            if (overlayImage && overlayImage.complete) {
                ctx.drawImage(overlayImage, 0, 0, uploadPreviewCanvas.width, uploadPreviewCanvas.height);
            }

            uploadPreviewCanvas.classList.remove('hidden');
        }
    }

    // Upload photo
    uploadBtn?.addEventListener('click', async () => {
        if (!uploadedFile) {
            showStatus('Please select an image first', 'error');
            return;
        }

        uploadBtn.disabled = true;
        showStatus('Uploading...', 'info');

        try {
            const formData = new FormData();
            formData.append('image', uploadedFile);
            formData.append('overlay', currentOverlay);

            const response = await App.fetch('/api/editor/upload', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-Token': App.getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const contentType = response.headers.get('content-type') || '';
            let result = null;

            if (contentType.includes('application/json')) {
                result = await response.json();
            } else {
                const text = await response.text();
                if (response.status === 413) {
                    showStatus('File is too large for server limits (5MB max).', 'error');
                    return;
                }
                console.error('Unexpected non-JSON upload response:', text.slice(0, 300));
                showStatus('Server returned an unexpected response', 'error');
                return;
            }

            if (result.success) {
                showStatus('Photo uploaded!', 'success');
                addImageToGallery(result.image);

                // Reset upload form
                fileInput.value = '';
                uploadPreview.src = '';
                uploadPreview.classList.add('hidden');
                uploadPreviewCanvas.classList.add('hidden');
                uploadPlaceholder.classList.remove('hidden');
                uploadedFile = null;
                updateActionButtons();
            } else {
                showStatus(result.error || 'Failed to upload photo', 'error');
            }
        } catch (error) {
            console.error('Upload error:', error);
            showStatus('Failed to upload photo', 'error');
        } finally {
            uploadBtn.disabled = false;
        }
    });

    // Add new image to my gallery
    function addImageToGallery(image) {
        if (!myImagesContainer) return;

        // Remove "no photos" message if exists
        const noPhotosMsg = myImagesContainer.querySelector('p');
        if (noPhotosMsg) {
            noPhotosMsg.remove();
        }

        const div = document.createElement('div');
        div.className = 'relative group';
        div.dataset.imageId = image.id;
        div.innerHTML = `
            <img
                src="${image.url}"
                alt="My photo"
                class="w-full aspect-square object-cover rounded-lg"
            >
            <button
                class="delete-image-btn absolute top-2 right-2 bg-red-500 text-white p-2 rounded-full opacity-0 group-hover:opacity-100 transition"
                data-image-id="${image.id}"
                title="Delete"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
            <div class="absolute bottom-2 left-2 flex space-x-2 text-white text-sm">
                <span class="bg-black bg-opacity-50 px-2 py-1 rounded">
                    <span class="likes-count">0</span> likes
                </span>
            </div>
        `;

        myImagesContainer.insertBefore(div, myImagesContainer.firstChild);
    }

    // Delete image
    myImagesContainer?.addEventListener('click', async (e) => {
        const deleteBtn = e.target.closest('.delete-image-btn');
        if (!deleteBtn) return;

        if (!confirm('Are you sure you want to delete this photo?')) {
            return;
        }

        const imageId = deleteBtn.dataset.imageId;
        const imageDiv = deleteBtn.closest('div[data-image-id]');

        try {
            const response = await App.fetch(`/api/editor/image/${imageId}`, {
                method: 'DELETE'
            });

            const result = await response.json();

            if (result.success) {
                imageDiv.remove();
                showStatus('Photo deleted', 'success');

                // Show "no photos" message if gallery is empty
                if (myImagesContainer.children.length === 0) {
                    myImagesContainer.innerHTML = '<p class="text-gray-500 col-span-full">No photos yet. Capture your first one!</p>';
                }
            } else {
                showStatus(result.error || 'Failed to delete photo', 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showStatus('Failed to delete photo', 'error');
        }
    });

    // Show status message
    function showStatus(message, type = 'info') {
        if (!statusMessage) return;

        const inner = statusMessage.querySelector('div');
        inner.textContent = message;

        inner.className = 'px-6 py-3 rounded-lg shadow-lg';
        switch (type) {
            case 'success':
                inner.classList.add('bg-green-600', 'text-white');
                break;
            case 'error':
                inner.classList.add('bg-red-600', 'text-white');
                break;
            default:
                inner.classList.add('bg-gray-800', 'text-white');
        }

        statusMessage.classList.remove('hidden');

        setTimeout(() => {
            statusMessage.classList.add('hidden');
        }, 3000);
    }

    // Initialize
    if (videoElement) {
        initWebcam();
    }
    updateActionButtons();
});
