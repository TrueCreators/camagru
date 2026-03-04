/**
 * Работа с веб-камерой через navigator.mediaDevices.getUserMedia()
 */

class Webcam {
    constructor(videoElement) {
        this.videoElement = videoElement;
        this.stream = null;
        this.isReady = false;
    }

    async start() {
        try {
            const constraints = {
                video: {
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: 'user'
                },
                audio: false
            };

            this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            this.videoElement.srcObject = this.stream;

            return new Promise((resolve) => {
                this.videoElement.onloadedmetadata = () => {
                    this.isReady = true;
                    resolve(true);
                };
            });
        } catch (error) {
            console.error('Webcam error:', error);
            this.isReady = false;
            throw error;
        }
    }

    stop() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
            this.isReady = false;
        }
    }

    capture(canvas, overlayImage = null) {
        if (!this.isReady) {
            throw new Error('Webcam not ready');
        }

        const ctx = canvas.getContext('2d');
        canvas.width = this.videoElement.videoWidth;
        canvas.height = this.videoElement.videoHeight;

        // Отрисовка кадра с видео
        ctx.drawImage(this.videoElement, 0, 0, canvas.width, canvas.height);

        // Отрисовка оверлея, если он передан
        if (overlayImage && overlayImage.complete) {
            ctx.drawImage(overlayImage, 0, 0, canvas.width, canvas.height);
        }

        return canvas.toDataURL('image/png');
    }

    getVideoSize() {
        return {
            width: this.videoElement.videoWidth,
            height: this.videoElement.videoHeight
        };
    }
}

// Сделать класс Webcam глобально доступным
window.Webcam = Webcam;
