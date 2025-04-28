<?php if(isset($is_dashboard) && $is_dashboard): ?>
            </main>
        </div>
    </div>
    <?php else: ?>
    </div> <!-- End of container -->
    
    <!-- Footer -->
    <footer class="bg-light text-center text-lg-start mt-5">
        <div class="container p-4">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
                    <h5 class="text-uppercase">DarasaLink</h5>
                    <p>
                        A comprehensive school management system that simplifies fee collection through M-Pesa integration.
                    </p>
                </div>

                <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
                    <h5 class="text-uppercase">Quick Links</h5>
                    <ul class="list-unstyled mb-0">
                        <li><a href="<?php echo base_url(); ?>" class="text-dark">Home</a></li>
                        <li><a href="<?php echo base_url('about'); ?>" class="text-dark">About</a></li>
                        <li><a href="<?php echo base_url('contact'); ?>" class="text-dark">Contact</a></li>
                        <li><a href="<?php echo base_url('auth/login'); ?>" class="text-dark">Login</a></li>
                    </ul>
                </div>

                <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
                    <h5 class="text-uppercase">Contact</h5>
                    <ul class="list-unstyled mb-0">
                        <li><i class="fas fa-map-marker-alt me-2"></i> 123 School Lane, Nairobi</li>
                        <li><i class="fas fa-envelope me-2"></i> info@darasalink.com</li>
                        <li><i class="fas fa-phone me-2"></i> +254 700 123456</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.05);">
            © <?php echo date('Y'); ?> DarasaLink. All rights reserved.
        </div>
    </footer>
    <?php endif; ?>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables -->
    <?php if(isset($load_datatables) && $load_datatables): ?>
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.datatable').DataTable();
        });
    </script>
    <?php endif; ?>
    
    <!-- Select2 -->
    <?php if(isset($load_select2) && $load_select2): ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2();
        });
    </script>
    <?php endif; ?>
    
    <!-- Chart.js -->
    <?php if(isset($load_charts) && $load_charts): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
    
    <!-- Custom JavaScript -->
    <?php if(isset($custom_js)): ?>
        <?php echo $custom_js; ?>
    <?php endif; ?>
</body>
</html>