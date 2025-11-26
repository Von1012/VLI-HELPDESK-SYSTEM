<?php 
    include 'init.php';
    if($users->isLoggedIn()) {
        header('Location: ticket.php');
    }
    $errorMessage = $users->login();
    include('inc/header.php');
    ?>
    <title>VLI HELPDESK</title>
    <?php include('inc/container.php');?>
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@500;700&display=swap" rel="stylesheet">

    <style>
    /* Full white background */
    /* Full background with subtle gradient */
html, body {
    height: 100%;
    margin: 0;
    font-family: 'Roboto', sans-serif;
    background: linear-gradient(135deg, #f7f7f7 0%, #e0e0e0 100%);
}

/* Optional: if you have a background image */
// body {
//     background: url('inc/background.jpg') no-repeat center center fixed;
//     background-size: cover;
// }

/* Center container */
.center-container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100%;
    padding: 15px;
    position: relative;
}

/* Login panel */
.login-panel {
    width: 400px;
    max-width: 100%;
    background: rgba(255, 255, 255, 0.95);
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    opacity: 0;
    transform: translateY(-50px);
    animation: slideDown 0.6s forwards;
}

/* Panel heading */
.panel-heading {
    background: #D32F2F;
    color: white;
    border-radius: 12px 12px 0 0;
    padding: 15px;
    font-weight: 600;
    text-align: center;
}

/* Logo */
.login-panel img {
    width: 140px;
    margin: 20px auto;
    display: block;
    opacity: 0;
    animation: fadeInLogo 0.8s forwards;
    animation-delay: 0.4s;
}

/* Login title */
.login-title {
    font-size: 2em;
    font-weight: 700;
    color: #D32F2F;
    text-align: center;
    margin-bottom: 25px;
    position: relative;
    opacity: 0;
    animation: fadeInTitle 0.8s forwards;
    animation-delay: 0.3s;
}

.login-title::after {
    content: '';
    display: block;
    width: 0;
    height: 3px;
    background: #D32F2F;
    margin: 10px auto 0;
    border-radius: 2px;
    animation: underlineGrow 0.5s forwards;
    animation-delay: 1.1s;
}

/* Input styling */
.login-panel input {
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 10px 15px;
    margin-bottom: 20px;
    width: 100%;
    transition: all 0.3s ease;
}

.login-panel input:focus {
    border-color: #D32F2F;
    box-shadow: 0 0 5px rgba(211, 47, 47, 0.5);
    outline: none;
}

/* Button */
.btn-block {
    width: 100%;
    background-color: #D32F2F;
    color: #fff;
    padding: 12px 0;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.btn-block:hover {
    background-color: #b71c1c;
    transform: translateY(-2px);
}

/* Animations */
@keyframes slideDown {
    to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInLogo { to { opacity: 1; } }
@keyframes fadeInTitle { to { opacity: 1; } }
@keyframes underlineGrow { to { width: 60px; } }

/* Mobile adjustments */
@media (max-height: 500px) {
    .center-container {
        align-items: flex-start;
        padding-top: 30px;
        padding-bottom: 30px;
    }
}

    </style>

    <div class="center-container">
        <div class="login-panel">
            <div class="login-title">Helpdesk System</div>
            <img src="inc/logo.png" alt="Logo">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <div class="panel-title">User Login</div>                        
                </div> 
                <div style="padding-top:30px" class="panel-body">
                    <?php if ($errorMessage != '') { ?>
                        <div id="login-alert" class="alert alert-danger col-sm-12">
                            <?php echo $errorMessage; ?>
                        </div>                            
                    <?php } ?>
                    <form id="loginform" class="form-horizontal" role="form" method="POST" action="">                                    
                        <div style="margin-bottom: 25px" class="input-group">
                            <span class="input-group-addon">
                                <i class="glyphicon glyphicon-user"></i>
                            </span>
                            <input type="text" class="form-control" id="email" name="email" placeholder="email" required>                                        
                        </div>                                
                        <div style="margin-bottom: 25px" class="input-group">
                            <span class="input-group-addon">
                                <i class="glyphicon glyphicon-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="password" required>
                        </div>
                        <div style="margin-top:10px" class="form-group">                               
                            <div class="col-sm-12 controls">
                                <input type="submit" name="login" value="Login" class="btn btn-block">                         
                            </div>                        
                        </div>  
                    </form>   
                </div>                     
            </div>  
        </div>
    </div>

    <?php include('inc/footer.php');?>