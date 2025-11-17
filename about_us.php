<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About Us - Aurum Scents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/about_us.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="customer_home.php">Aurum Scents</a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="nav-links-container mx-auto">
                <a class="nav-link" href="customer_home.php">Home</a>
                <a class="nav-link active" href="about_us.php">About Us</a>
                <a class="nav-link" href="buy_here.php">Buy Here</a>
                <a class="nav-link" href="contact_us.php">Contact Us</a>
                <a class="nav-link" href="rating.php">Rate Us</a>
            </div>

            <div class="icons-container">
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fa fa-user"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                        <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                        <li><a class="dropdown-item" href="points.php">My Points</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="login_customer.php">Log Out</a></li>
                    </ul>
                </div>

                <a class="nav-link" href="cart.php">
                    <i class="fa fa-shopping-cart"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- About Us Section -->
<section class="container mt-5 mb-5 text-light">

    <!-- Section Title -->
    <h1 class="text-center mb-5" style="color: #eacb99; font-weight: 700;">About Aurum Scents</h1>

    <!-- Row 1: Text → Image -->
    <div class="row align-items-center mb-5">
        <div class="col-md-6">
            <h3 class="mb-3" style="color: #f5daa7;">Our Story</h3>
            <p style="font-size: 1.1rem; line-height: 1.8;">
                Aurum Scents was founded by four passionate 3rd year BS-IT students as part of their ITDBADM project. 
                What started as a simple academic requirement grew into a vision. Bringing luxurious, high-quality 
                fragrances from around the world to Filipino consumers.  
                <br><br>
                Our goal is simple: to make premium scents accessible, elegant, and unforgettable.
            </p>
        </div>
        <div class="col-md-6 text-center">
            <img src="images/about1.jpg" alt="About Image 1" class="img-fluid rounded-4 shadow" style="max-height: 350px; object-fit: cover;">
        </div>
    </div>

    <!-- Row 2: Image → Text -->
    <div class="row align-items-center mb-5 flex-md-row-reverse">
        <div class="col-md-6">
            <h3 class="mb-3" style="color: #f5daa7;">Our Mission</h3>
            <p style="font-size: 1.1rem; line-height: 1.8;">
                Aurum Scents imports unique perfumes from top fragrance creators around the world.  
                Our team carefully curates each product to ensure authenticity, sophistication, and exceptional quality.  
                <br><br>
                Whether you're looking for floral, citrusy, woody, or signature scents, we aim to deliver fragrances that 
                elevate confidence and express individuality.
            </p>
        </div>
        <div class="col-md-6 text-center">
            <img src="images/about2.jpg" alt="About Image 2" class="img-fluid rounded-4 shadow" style="max-height: 350px; object-fit: cover;">
        </div>
    </div>

    <!-- Buy Button -->
    <div class="text-center mt-4">
        <a href="buy_here.php" class="btn px-4 py-2" 
            style="background-color: #eacb99; color: #5a0f1a; font-weight: 700; border-radius: 30px; font-size: 1.2rem;">
            Shop Our Fragrances
        </a>
    </div>

</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
