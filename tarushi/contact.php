<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "reviewdb";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Sanitize inputs
        $name = htmlspecialchars($_POST['name']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $rating = intval($_POST['rating']);
        $subject = htmlspecialchars($_POST['subject']);
        $feedback = htmlspecialchars($_POST['feedback']);

        // Validate inputs
        if (empty($name) || empty($email) || empty($rating) || empty($subject) || empty($feedback)) {
            throw new Exception("All fields are required");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Prepare and execute statement
        $stmt = $conn->prepare("INSERT INTO feedback (name, email, rating, subject, feedback) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $name, $email, $rating, $subject, $feedback);
        
        if (!$stmt->execute()) {
            throw new Exception("Error submitting feedback: " . $stmt->error);
        }

        $success_message = "Thank you for your feedback! Your review has been submitted.";
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EncodeX | Customer Feedback</title>
    <link rel="icon" href="feedback.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link href="./output.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0a192f;
            --secondary: #64ffda;
            --accent: #ff5555;
            --light: #ccd6f6;
            --dark: #020c1b;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Press Start 2P';  
        }
        
        body {
            background-color: var(--dark);
            color: var(--light);
            line-height: 1.6;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 0;
        }
        
        .feedback-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-top: 4rem;
        }
        
        .feedback-form {
            background: rgba(23, 42, 69, 0.7);
            padding: 2.5rem;
            border-radius: 8px;
            border: 1px solid rgba(100, 255, 218, 0.2);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .section-title {
            margin-bottom: 2rem;
            position: relative;
        }
        
        .section-title h2 {
            font-size: 2rem;
            color: var(--light);
            margin-bottom: 0.5rem;
        }
        
        .section-title h2 span {
            color: var(--secondary);
        }
        
        .section-title p {
            color: rgba(204, 214, 246, 0.6);
            font-size: 0.95rem;
        }
        
        .section-title::after {
            content: '';
            display: block;
            width: 70px;
            height: 2px;
            background: var(--secondary);
            margin-top: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--secondary);
            font-size: 0.9rem;
            font-family: 'Press Start 2P';  
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            background: rgba(10, 25, 47, 0.5);
            border: 1px solid rgba(100, 255, 218, 0.2);
            border-radius: 4px;
            color: var(--light);
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 2px rgba(100, 255, 218, 0.2);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .rating-input {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .rating-input input {
            display: none;
        }
        
        .rating-input label {
            font-size: 1.5rem;
            color: rgba(204, 214, 246, 0.3);
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .rating-input input:checked ~ label,
        .rating-input label:hover,
        .rating-input label:hover ~ label {
            color: var(--secondary);
        }
        
        .btn {
            display: inline-block;
            background: var(--secondary);
            color: var(--primary);
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            font-family: 'Press Start 2P';  
            font-weight: 600;
        }
        
        .btn:hover {
            background: rgba(100, 255, 218, 0.8);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(100, 255, 218, 0.3);
        }
        
        .reviews-container {
            max-height: 600px;
            overflow-y: auto;
            padding-right: 1rem;
        }
        
        .reviews-container::-webkit-scrollbar {
            width: 6px;
        }
        
        .reviews-container::-webkit-scrollbar-track {
            background: rgba(10, 25, 47, 0.5);
            border-radius: 10px;
        }
        
        .reviews-container::-webkit-scrollbar-thumb {
            background: var(--secondary);
            border-radius: 10px;
        }
        
        .review-card {
            background: rgba(23, 42, 69, 0.7);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(100, 255, 218, 0.1);
            transition: all 0.3s;
        }
        
        .review-card:hover {
            border-color: var(--secondary);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .review-user {
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.2rem;
            color: var(--secondary);
            font-weight: 600;
            border: 2px solid var(--secondary);
            font-size: 1.1rem;
        }
        
        .user-info h4 {
            color: var(--light);
            margin-bottom: 0.2rem;
            font-size: 1.1rem;
        }
        
        .user-info p {
            color: rgba(204, 214, 246, 0.5);
            font-size: 0.85rem;
        }
        
        .review-rating {
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 1.1rem;
        }
        
        .review-content h3 {
            color: var(--light);
            margin-bottom: 0.8rem;
            font-size: 1.2rem;
        }
        
        .review-content p {
            color: rgba(204, 214, 246, 0.8);
            font-size: 0.85rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        
        .review-date {
            color: rgba(204, 214, 246, 0.4);
            font-size: 0.8rem;
            display: block;
            text-align: right;
            font-family: 'Press Start 2P';  
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .alert-success {
            background: rgba(100, 255, 218, 0.1);
            border: 1px solid var(--secondary);
            color: var(--secondary);
        }
        
        .alert-error {
            background: rgba(255, 85, 85, 0.1);
            border: 1px solid var(--accent);
            color: var(--accent);
        }

        @media (max-width: 768px) {
            .feedback-section {
                grid-template-columns: 1fr;
            }
            
            .feedback-form {
                padding: 1.5rem;
            }
            
            .section-title h2 {
                font-size: 1.5rem;
            }
            
            .user-avatar {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?= $error_message ?></div>
        <?php endif; ?>

        <div class="feedback-section">
            <div class="feedback-form">
                <div class="section-title">
                    <h2>Share Your <span>Feedback</span></h2>
                    <p>Help us improve EncodeX by sharing your experience</p>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" 
                               placeholder="Enter your name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               placeholder="Enter your email" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Your Rating</label>
                        <div class="rating-input">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="star<?= $i ?>" name="rating" 
                                       value="<?= $i ?>" <?= ($_POST['rating'] ?? '') == $i ? 'checked' : '' ?> required>
                                <label for="star<?= $i ?>"><i class="fas fa-star"></i></label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-control"
                               value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"
                               placeholder="Briefly describe your feedback" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="feedback">Your Feedback</label>
                        <textarea id="feedback" name="feedback" class="form-control" 
                                  placeholder="Tell us about your experience with EncodeX" 
                                  required><?= htmlspecialchars($_POST['feedback'] ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn">Submit Feedback</button>
                </form>
            </div>
            
            <div class="reviews-section">
                <div class="section-title">
                    <h2>Customer <span>Reviews</span></h2>
                    <p>What our users say about EncodeX security solutions</p>
                </div>
                
                <div class="reviews-container">
   
    <div class="review-card">
        <div class="review-header">
            <div class="review-user">
                <div class="user-avatar">AL</div>
                <div class="user-info">
                    <h4>Amira Lee</h4>
                    <p>Compliance Officer</p>
                </div>
            </div>
            <div class="review-rating">
                4.0 <i class="fas fa-star"></i>
            </div>
        </div>
        <div class="review-content">
            <h3>Meets Strict Compliance Requirements</h3>
            <p>EncodeX has helped us meet our industry's strict compliance requirements for secure communications. The audit logs and security reports are comprehensive and saved us countless hours during our last compliance review.</p>
            <span class="review-date">Posted on: February 22, 2023</span>
        </div>
    </div>

    <!-- Sample Review 2 -->
    <div class="review-card">
        <div class="review-header">
            <div class="review-user">
                <div class="user-avatar">DG</div>
                <div class="user-info">
                    <h4>David Gonzalez</h4>
                    <p>Network Engineer</p>
                </div>
            </div>
            <div class="review-rating">
                5.0 <i class="fas fa-star"></i>
            </div>
        </div>
        <div class="review-content">
            <h3>Technical Excellence</h3>
            <p>The engineering behind EncodeX is top-notch. As someone who understands the cryptography involved, I appreciate how they've balanced security and performance. The perfect forward secrecy implementation is particularly robust.</p>
            <span class="review-date">Posted on: January 15, 2023</span>
        </div>
    </div>

    <!-- Sample Review 3 -->
    <div class="review-card">
        <div class="review-header">
            <div class="review-user">
                <div class="user-avatar">JS</div>
                <div class="user-info">
                    <h4>Jessica Smith</h4>
                    <p>IT Manager</p>
                </div>
            </div>
            <div class="review-rating">
                4.5 <i class="fas fa-star"></i>
            </div>
        </div>
        <div class="review-content">
            <h3>Improved Team Collaboration</h3>
            <p>EncodeX has significantly improved our team's collaboration. The secure file-sharing feature is easy to use and ensures our sensitive data remains protected. Highly recommended!</p>
            <span class="review-date">Posted on: March 10, 2023</span>
        </div>
    </div>

    <!-- Sample Review 4 -->
    <div class="review-card">
        <div class="review-header">
            <div class="review-user">
                <div class="user-avatar">RK</div>
                <div class="user-info">
                    <h4>Rajesh Kumar</h4>
                    <p>Software Developer</p>
                </div>
            </div>
            <div class="review-rating">
                5.0 <i class="fas fa-star"></i>
            </div>
        </div>
        <div class="review-content">
            <h3>Exceptional Customer Support</h3>
            <p>The customer support team at EncodeX is fantastic. They resolved my issue within hours and even provided additional tips to optimize our usage of the platform. Great service!</p>
            <span class="review-date">Posted on: April 5, 2023</span>
        </div>
    </div>

    <!-- Sample Review 5 -->
    <div class="review-card">
        <div class="review-header">
            <div class="review-user">
                <div class="user-avatar">MT</div>
                <div class="user-info">
                    <h4>Michael Thompson</h4>
                    <p>Business Analyst</p>
                </div>
            </div>
            <div class="review-rating">
                4.8 <i class="fas fa-star"></i>
            </div>
        </div>
        <div class="review-content">
            <h3>Reliable and Secure</h3>
            <p>EncodeX has become an integral part of our business operations. The platform is reliable, secure, and easy to use. I would recommend it to anyone looking for a robust security solution.</p>
            <span class="review-date">Posted on: May 1, 2023</span>
        </div>
    </div>
</div>
            </div>
        </div>
    </div>

    <script>
        // Auto-scroll functionality
        const reviewsContainer = document.querySelector('.reviews-container');
        
        const reviewsContainer = document.querySelector('.reviews-container');
    
    function smoothScroll() {
        if (reviewsContainer.scrollTop + reviewsContainer.clientHeight >= reviewsContainer.scrollHeight) {
            reviewsContainer.scrollTop = 0;
        } else {
            reviewsContainer.scrollTop += 1; // Adjust the scroll step if needed
        }
        setTimeout(smoothScroll, 5000); // Increase the interval to slow down scrolling (e.g., 50ms)
    }

    // Start auto-scroll after 5 seconds
    setTimeout(() => {
        if (reviewsContainer.scrollHeight > reviewsContainer.clientHeight) {
            smoothScroll();
        }
    }, 5000);

       
        document.querySelector('form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                window.location.reload();
            } catch (error) {
                alert('An error occurred. Please try again.');
            }
        });
    </script>
</body>
</html>