/**
 * Gallery functionality - likes, comments, infinite scroll
 */

document.addEventListener('DOMContentLoaded', () => {
    const galleryContainer = document.getElementById('gallery-container');
    const loadMoreBtn = document.getElementById('load-more-btn');
    const loadingSpinner = document.getElementById('loading-spinner');

    let currentPage = parseInt(loadMoreBtn?.dataset.page || 1) - 1;
    let isLoading = false;
    let hasMore = !!loadMoreBtn;

    // Like functionality
    galleryContainer?.addEventListener('click', async (e) => {
        const likeBtn = e.target.closest('.like-btn');
        if (!likeBtn || likeBtn.disabled) return;

        const imageId = likeBtn.dataset.imageId;

        try {
            const response = await App.fetch(`/api/gallery/like/${imageId}`, {
                method: 'POST'
            });

            const result = await response.json();

            if (result.success) {
                // Update UI
                const likesCount = likeBtn.querySelector('.likes-count');
                likesCount.textContent = result.count;

                const svg = likeBtn.querySelector('svg');
                if (result.liked) {
                    likeBtn.classList.add('text-red-500');
                    svg.classList.add('fill-current');
                } else {
                    likeBtn.classList.remove('text-red-500');
                    svg.classList.remove('fill-current');
                }
            } else if (response.status === 401) {
                window.location.href = '/login';
            } else {
                App.showMessage(result.error || 'Failed to like', 'error');
            }
        } catch (error) {
            console.error('Like error:', error);
        }
    });

    // Comments toggle
    galleryContainer?.addEventListener('click', async (e) => {
        const toggleBtn = e.target.closest('.comments-toggle');
        if (!toggleBtn) return;

        const imageId = toggleBtn.dataset.imageId;
        const card = toggleBtn.closest('.image-card');
        const commentsSection = card.querySelector('.comments-section');
        const commentsList = card.querySelector('.comments-list');

        if (commentsSection.classList.contains('hidden')) {
            commentsSection.classList.remove('hidden');

            // Load comments
            try {
                const response = await App.fetch(`/api/gallery/comments/${imageId}`);
                const result = await response.json();

                if (result.success) {
                    renderComments(commentsList, result.data);
                }
            } catch (error) {
                console.error('Load comments error:', error);
                commentsList.innerHTML = '<p class="text-red-500">Failed to load comments</p>';
            }
        } else {
            commentsSection.classList.add('hidden');
        }
    });

    // Render comments
    function renderComments(container, comments) {
        if (comments.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-sm">No comments yet. Be the first!</p>';
            return;
        }

        container.innerHTML = comments.map(comment => `
            <div class="flex space-x-3">
                <div class="flex-1">
                    <p class="text-sm">
                        <span class="font-semibold">${App.escapeHtml(comment.username)}</span>
                        ${App.escapeHtml(comment.content)}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">${App.timeAgo(comment.created_at)}</p>
                </div>
            </div>
        `).join('');
    }

    // Comment submission
    galleryContainer?.addEventListener('submit', async (e) => {
        const form = e.target.closest('.comment-form');
        if (!form) return;

        e.preventDefault();

        const imageId = form.dataset.imageId;
        const input = form.querySelector('input[name="content"]');
        const content = input.value.trim();

        if (!content) return;

        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;

        try {
            const response = await App.fetch(`/api/gallery/comment/${imageId}`, {
                method: 'POST',
                body: { content }
            });

            const result = await response.json();

            if (result.success) {
                // Add new comment to list
                const commentsList = form.closest('.comments-section').querySelector('.comments-list');
                const noComments = commentsList.querySelector('p.text-gray-500');
                if (noComments) {
                    commentsList.innerHTML = '';
                }

                const commentDiv = document.createElement('div');
                commentDiv.className = 'flex space-x-3';
                commentDiv.innerHTML = `
                    <div class="flex-1">
                        <p class="text-sm">
                            <span class="font-semibold">${App.escapeHtml(result.comment.username)}</span>
                            ${App.escapeHtml(result.comment.content)}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">just now</p>
                    </div>
                `;
                commentsList.insertBefore(commentDiv, commentsList.firstChild);

                // Update comment count
                const card = form.closest('.image-card');
                const countSpan = card.querySelector('.comments-count');
                countSpan.textContent = parseInt(countSpan.textContent) + 1;

                // Clear input
                input.value = '';
            } else if (response.status === 401) {
                window.location.href = '/login';
            } else {
                App.showMessage(result.error || 'Failed to post comment', 'error');
            }
        } catch (error) {
            console.error('Comment error:', error);
            App.showMessage('Failed to post comment', 'error');
        } finally {
            submitBtn.disabled = false;
        }
    });

    // Share functionality
    galleryContainer?.addEventListener('click', async (e) => {
        const shareBtn = e.target.closest('.share-btn');
        if (!shareBtn) return;

        const url = shareBtn.dataset.url;

        if (navigator.share) {
            try {
                await navigator.share({
                    title: 'Check out this photo on Camagru',
                    url: url
                });
            } catch (err) {
                if (err.name !== 'AbortError') {
                    copyToClipboard(url);
                }
            }
        } else {
            copyToClipboard(url);
        }
    });

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            App.showMessage('Link copied to clipboard!', 'success');
        }).catch(() => {
            // Fallback
            const input = document.createElement('input');
            input.value = text;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            App.showMessage('Link copied to clipboard!', 'success');
        });
    }

    // Load more / Infinite scroll
    loadMoreBtn?.addEventListener('click', loadMore);

    async function loadMore() {
        if (isLoading || !hasMore) return;

        isLoading = true;
        loadMoreBtn?.classList.add('hidden');
        loadingSpinner?.classList.remove('hidden');

        try {
            currentPage++;
            const response = await App.fetch(`/api/gallery?page=${currentPage}&limit=5`);
            const result = await response.json();

            if (result.success) {
                result.data.forEach(image => {
                    const card = createImageCard(image);
                    galleryContainer.appendChild(card);
                });

                hasMore = result.pagination.has_more;

                if (hasMore) {
                    loadMoreBtn?.classList.remove('hidden');
                    loadMoreBtn.dataset.page = currentPage + 1;
                }
            }
        } catch (error) {
            console.error('Load more error:', error);
            currentPage--;
            loadMoreBtn?.classList.remove('hidden');
        } finally {
            isLoading = false;
            loadingSpinner?.classList.add('hidden');
        }
    }

    // Create image card from data
    function createImageCard(image) {
        const isLoggedIn = !!document.querySelector('form[action="/logout"]');
        const userLiked = image.user_liked > 0;

        const article = document.createElement('article');
        article.className = 'bg-white rounded-lg shadow-md overflow-hidden image-card';
        article.dataset.imageId = image.id;

        article.innerHTML = `
            <div class="relative">
                <img
                    src="/uploads/images/${App.escapeHtml(image.filename)}"
                    alt="Photo by ${App.escapeHtml(image.username)}"
                    class="w-full h-auto"
                    loading="lazy"
                >
            </div>
            <div class="p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="font-semibold text-gray-800">${App.escapeHtml(image.username)}</span>
                    <span class="text-sm text-gray-500">${App.timeAgo(image.created_at)}</span>
                </div>
                <div class="flex items-center space-x-6">
                    <button
                        class="like-btn flex items-center space-x-2 ${isLoggedIn ? 'hover:text-red-500' : 'cursor-default'} transition ${userLiked ? 'text-red-500' : 'text-gray-600'}"
                        data-image-id="${image.id}"
                        ${!isLoggedIn ? 'disabled title="Login to like"' : ''}
                    >
                        <svg class="w-6 h-6 ${userLiked ? 'fill-current' : ''}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                        <span class="likes-count">${image.likes_count}</span>
                    </button>
                    <button
                        class="comments-toggle flex items-center space-x-2 text-gray-600 hover:text-blue-500 transition"
                        data-image-id="${image.id}"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <span class="comments-count">${image.comments_count}</span>
                    </button>
                    <button
                        class="share-btn flex items-center space-x-2 text-gray-600 hover:text-green-500 transition"
                        data-image-id="${image.id}"
                        data-url="${window.location.origin}/gallery#${image.id}"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                        </svg>
                    </button>
                </div>
                <div class="comments-section hidden mt-4 pt-4 border-t">
                    <div class="comments-list space-y-3 max-h-60 overflow-y-auto"></div>
                    ${isLoggedIn ? `
                        <form class="comment-form mt-4 flex space-x-2" data-image-id="${image.id}">
                            <input
                                type="text"
                                name="content"
                                placeholder="Add a comment..."
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required
                                maxlength="1000"
                            >
                            <button
                                type="submit"
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
                            >
                                Post
                            </button>
                        </form>
                    ` : `
                        <p class="mt-4 text-gray-500 text-sm">
                            <a href="/login" class="text-blue-600 hover:underline">Login</a> to comment
                        </p>
                    `}
                </div>
            </div>
        `;

        return article;
    }

    // Infinite scroll (optional - can be enabled)
    let infiniteScrollEnabled = false;

    if (infiniteScrollEnabled) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && hasMore && !isLoading) {
                    loadMore();
                }
            });
        }, { rootMargin: '200px' });

        if (loadMoreBtn) {
            observer.observe(loadMoreBtn.parentElement);
        }
    }
});
