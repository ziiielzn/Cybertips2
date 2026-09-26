// Course progress tracking
class CourseProgress {
    constructor(courseId, totalSlides) {
        this.courseId = courseId;
        this.totalSlides = totalSlides;
        this.currentSlide = 1;
        this.updateInterval = null;
    }

    // Initialize progress tracking
    init() {
        // Start periodic progress updates
        this.updateInterval = setInterval(() => this.updateProgress(), 30000); // Update every 30 seconds
        
        // Update on page unload
        window.addEventListener('beforeunload', () => {
            this.updateProgress();
            clearInterval(this.updateInterval);
        });
    }

    // Update the current slide
    setCurrentSlide(slideNumber) {
        this.currentSlide = slideNumber;
        this.updateProgress();
    }

    // Send progress update to server
    updateProgress() {
        fetch('update_progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            credentials: 'include',
            body: new URLSearchParams({
                'course_id': this.courseId,
                'current_slide': this.currentSlide,
                'total_slides': this.totalSlides
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Progress updated:', data.data.percentage + '%');
            } else {
                console.error('Failed to update progress:', data.error);
            }
        })
        .catch(error => {
            console.error('Error updating progress:', error);
        });
    }
}

// Export the class
window.CourseProgress = CourseProgress; 