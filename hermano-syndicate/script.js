// Dropdown menu functionality
function toggleDropdown() {
    const dropdown = document.getElementById('dropdown');
    dropdown.classList.toggle('active');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('dropdown');
    const dropdownBtn = dropdown.querySelector('.dropdown-btn');
    
    if (!dropdown.contains(event.target)) {
        dropdown.classList.remove('active');
    }
});

// Gallery functionality
let galleryImages = [];
let currentSection = 'members';

function showGallery() {
    console.log('showGallery() called');
    
    if (currentSection === 'gallery') return;
    
    // Hide members section
    const membersSection = document.getElementById('members-section');
    if (membersSection) {
        membersSection.style.display = 'none';
    }
    
    // Show gallery section
    const gallerySection = document.getElementById('gallery-section');
    if (gallerySection) {
        gallerySection.style.display = 'block';
        
        // Set current section
        currentSection = 'gallery';
        
        // Add smooth transition
        gallerySection.style.opacity = '0';
        gallerySection.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            gallerySection.style.transition = 'all 0.5s ease';
            gallerySection.style.opacity = '1';
            gallerySection.style.transform = 'translateY(0)';
        }, 100);
        
        // Load gallery images
        loadGalleryImages();
    }
    
    // Close dropdown
    const dropdown = document.getElementById('dropdown');
    if (dropdown) {
        dropdown.classList.remove('active');
    }
    
    console.log('Gallery section shown');
}

function showMembers() {
    console.log('showMembers() called');
    
    if (currentSection === 'members') return;
    
    const membersSection = document.getElementById('members-section');
    const gallerySection = document.getElementById('gallery-section');
    
    // Close dropdown
    const dropdown = document.getElementById('dropdown');
    if (dropdown) {
        dropdown.classList.remove('active');
    }
    
    if (gallerySection && membersSection) {
        // Fade out gallery
        gallerySection.style.transition = 'all 0.3s ease';
        gallerySection.style.opacity = '0';
        gallerySection.style.transform = 'translateY(-30px)';
        
        setTimeout(() => {
            gallerySection.style.display = 'none';
            membersSection.style.display = 'block';
            currentSection = 'members';
            
            // Fade in members
            membersSection.style.opacity = '0';
            membersSection.style.transform = 'translateY(30px)';
            membersSection.style.transition = 'all 0.5s ease';
            
            setTimeout(() => {
                membersSection.style.opacity = '1';
                membersSection.style.transform = 'translateY(0)';
            }, 50);
        }, 300);
    }
    
    console.log('Members section shown');
}

async function loadGalleryImages() {
    console.log('Loading gallery images...');
    
    const loadingEl = document.getElementById('gallery-loading');
    const gridEl = document.getElementById('gallery-grid');
    const emptyEl = document.getElementById('gallery-empty');
    const countEl = document.getElementById('gallery-count');

    // Show loading state
    if (loadingEl) loadingEl.style.display = 'block';
    if (gridEl) gridEl.innerHTML = '';
    if (emptyEl) emptyEl.style.display = 'none';

    try {
        const response = await fetch('public_gallery.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Gallery data received:', data);

        if (loadingEl) loadingEl.style.display = 'none';

        if (data.success && data.images && data.images.length > 0) {
            galleryImages = data.images;
            if (countEl) countEl.textContent = `${data.count} image${data.count !== 1 ? 's' : ''}`;
            
            // Create image elements
            if (gridEl) {
                data.images.forEach((image, index) => {
                    const imageEl = createImageElement(image, index);
                    gridEl.appendChild(imageEl);
                });

                // Staggered animation for images
                setTimeout(() => {
                    const images = gridEl.querySelectorAll('.gallery-item');
                    images.forEach((img, i) => {
                        setTimeout(() => {
                            img.style.opacity = '1';
                            img.style.transform = 'translateY(0)';
                        }, i * 150);
                    });
                }, 200);
            }
        } else {
            if (emptyEl) emptyEl.style.display = 'block';
            if (countEl) countEl.textContent = 'No images available';
            console.log('No images found in gallery');
        }
    } catch (error) {
        console.error('Error loading gallery:', error);
        if (loadingEl) loadingEl.style.display = 'none';
        if (emptyEl) emptyEl.style.display = 'block';
        if (countEl) countEl.textContent = 'Error loading images';
        
        // Show error message in empty state
        if (emptyEl) {
            emptyEl.innerHTML = `
                <div class="empty-icon">❌</div>
                <p>Error loading gallery images</p>
                <small>Please try refreshing the page</small>
            `;
            emptyEl.style.display = 'block';
        }
    }
}

function createImageElement(image, index) {
    const div = document.createElement('div');
    div.className = 'gallery-item';
    div.style.opacity = '0';
    div.style.transform = 'translateY(50px)';
    div.style.transition = 'all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
    
    // Truncate description for preview
    const description = image.description || '';
    const truncatedDesc = description.length > 100 
        ? description.substring(0, 100) + '...' 
        : description;
    
    div.innerHTML = `
        <div class="image-container" onclick="openImageModal(${index})">
            <img src="${escapeHtml(image.url)}" alt="${escapeHtml(image.title)}" loading="lazy" onerror="handleImageError(this)">
            <div class="image-overlay">
                <div class="overlay-content">
                    <h4>${escapeHtml(image.title)}</h4>
                    <p class="uploader">by ${escapeHtml(image.uploader)}</p>
                    <p class="date">${escapeHtml(image.date)}</p>
                </div>
            </div>
        </div>
        <div class="image-info">
            <h4>${escapeHtml(image.title)}</h4>
            ${truncatedDesc ? `<p class="description">${escapeHtml(truncatedDesc)}</p>` : ''}
            <div class="meta">
                <span>by ${escapeHtml(image.uploader)}</span>
                <span>${escapeHtml(image.date)}</span>
            </div>
        </div>
    `;
    
    return div;
}

function handleImageError(img) {
    console.log('Image failed to load:', img.src);
    img.style.display = 'none';
    const container = img.closest('.gallery-item');
    if (container) {
        container.innerHTML = `
            <div class="image-error">
                <div class="error-icon">📷</div>
                <p>Image not found</p>
            </div>
        `;
        container.style.opacity = '0.5';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function openImageModal(index) {
    const image = galleryImages[index];
    if (!image) return;

    console.log('Opening modal for image:', image.title);

    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const modalTitle = document.getElementById('modalTitle');
    const modalDesc = document.getElementById('modalDescription');
    const modalUploader = document.getElementById('modalUploader');
    const modalDate = document.getElementById('modalDate');

    if (!modal) {
        console.error('Modal element not found');
        return;
    }

    if (modalImg) modalImg.src = image.url;
    if (modalTitle) modalTitle.textContent = image.title;
    if (modalDesc) modalDesc.textContent = image.description || 'No description available';
    if (modalUploader) modalUploader.textContent = `Uploaded by ${image.uploader}`;
    if (modalDate) modalDate.textContent = image.date;
    
    modal.style.display = 'block';
    
    // Add entrance animation
    const modalContent = modal.querySelector('.modal-content');
    if (modalContent) {
        setTimeout(() => {
            modalContent.style.transform = 'scale(1)';
            modalContent.style.opacity = '1';
        }, 10);
    }
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('imageModal');
    if (!modal) return;
    
    const modalContent = modal.querySelector('.modal-content');
    
    if (modalContent) {
        // Exit animation
        modalContent.style.transform = 'scale(0.9)';
        modalContent.style.opacity = '0';
    }
    
    setTimeout(() => {
        modal.style.display = 'none';
        if (modalContent) {
            modalContent.style.transform = 'scale(1)';
            modalContent.style.opacity = '1';
        }
    }, 300);
    
    // Restore body scroll
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('imageModal');
    if (modal && event.target === modal) {
        closeModal();
    }
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    const dropdown = document.getElementById('dropdown');
    
    if (e.key === 'Escape') {
        if (dropdown && dropdown.classList.contains('active')) {
            dropdown.classList.remove('active');
            const dropdownBtn = dropdown.querySelector('.dropdown-btn');
            if (dropdownBtn) dropdownBtn.focus();
        } else {
            closeModal();
        }
    }
});

// Smooth scrolling for dropdown links (excluding gallery)
document.addEventListener('DOMContentLoaded', function() {
    const dropdownLinks = document.querySelectorAll('.dropdown-content a');
    
    dropdownLinks.forEach(link => {
        // Skip special handling for gallery and members links as they have onclick handlers
        const linkText = link.textContent.trim();
        if (linkText === 'Gallery' || linkText === 'Members') {
            return;
        }
        
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            // Only apply smooth scrolling to anchor links
            if (href && href.startsWith('#')) {
                e.preventDefault();
                const targetElement = document.querySelector(href);
                
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
            
            // Close dropdown after clicking
            const dropdown = document.getElementById('dropdown');
            if (dropdown) {
                dropdown.classList.remove('active');
            }
        });
    });

    // Add intersection observer for profile panels animation
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Initialize animations and effects
    const profilePanels = document.querySelectorAll('.profile-panel');
    
    // Set initial state for animation
    profilePanels.forEach((panel, index) => {
        panel.style.opacity = '0';
        panel.style.transform = 'translateY(50px)';
        panel.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
        
        // Observe each panel
        observer.observe(panel);
    });

    // Enhanced hover effects for profile panels
    profilePanels.forEach(panel => {
        panel.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-15px) scale(1.02)';
            
            const socialLinks = this.querySelectorAll('.social-link');
            socialLinks.forEach((link, index) => {
                setTimeout(() => {
                    link.style.transform = 'translateY(-2px)';
                    link.style.opacity = '1';
                }, index * 50);
            });
        });

        panel.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
            
            const socialLinks = this.querySelectorAll('.social-link');
            socialLinks.forEach(link => {
                link.style.transform = 'translateY(0)';
                link.style.opacity = '0.9';
            });
        });
    });

    // Add typing effect to the title
    const title = document.querySelector('#members-section .title');
    if (title) {
        const titleText = title.textContent;
        title.textContent = '';
        
        let i = 0;
        const typeWriter = function() {
            if (i < titleText.length) {
                title.textContent += titleText.charAt(i);
                i++;
                setTimeout(typeWriter, 100);
            }
        };
        
        // Start the typing effect
        typeWriter();
    }
});