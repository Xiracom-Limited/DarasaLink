<?php $this->load->view('header'); ?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-6">
            <h1 class="display-4 fw-bold">Welcome to DarasaLink</h1>
            <p class="lead">A comprehensive school management system with M-Pesa payment integration.</p>
            <p>DarasaLink simplifies school fee management by providing an easy way to track student payments, generate fee reports, and allow parents to pay school fees using M-Pesa.</p>
            <div class="mt-4">
                <a href="<?php echo base_url('auth/login'); ?>" class="btn btn-primary me-2">Login</a>
                <a href="<?php echo base_url('about'); ?>" class="btn btn-outline-secondary">Learn More</a>
            </div>
        </div>
        <div class="col-md-6 text-center">
            <img src="https://via.placeholder.com/500x300?text=DarasaLink" alt="DarasaLink" class="img-fluid rounded shadow">
        </div>
    </div>
    
    <hr class="my-5">
    
    <div class="row mb-5">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-money-bill-wave fa-3x text-primary"></i>
                    </div>
                    <h3>Fee Management</h3>
                    <p>Easily create and manage fee structures for different classes and students.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-mobile-alt fa-3x text-primary"></i>
                    </div>
                    <h3>M-Pesa Integration</h3>
                    <p>Seamlessly collect payments through M-Pesa for convenient fee collection.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-chart-bar fa-3x text-primary"></i>
                    </div>
                    <h3>Advanced Reporting</h3>
                    <p>Generate insightful reports on fee collection and student payment status.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('footer'); ?>