<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4>Payment Status</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info text-center">
                        <h5>Please check your phone for an M-Pesa STK Push notification</h5>
                        <p>Enter your M-Pesa PIN to complete the payment.</p>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th>Order ID:</th>
                                <td><?php echo $payment->order_id; ?></td>
                            </tr>
                            <tr>
                                <th>Amount:</th>
                                <td><?php echo number_format($payment->amount, 2) . ' KES'; ?></td>
                            </tr>
                            <tr>
                                <th>Phone Number:</th>
                                <td><?php echo $payment->phone_number; ?></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <?php if ($payment->status == 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php elseif ($payment->status == 'success'): ?>
                                        <span class="badge bg-success">Success</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Failed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ($payment->status == 'success'): ?>
                            <tr>
                                <th>M-Pesa Reference:</th>
                                <td><?php echo $payment->mpesa_reference; ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($payment->status == 'failed' && !empty($payment->notes)): ?>
                            <tr>
                                <th>Reason:</th>
                                <td><?php echo $payment->notes; ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                    
                    <?php if ($payment->status == 'pending'): ?>
                    <div class="text-center mt-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Waiting for payment confirmation...</p>
                        <small class="text-muted">This page will automatically refresh every 10 seconds.</small>
                    </div>
                    <?php elseif ($payment->status == 'success'): ?>
                    <div class="text-center mt-4">
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle fa-3x mb-3"></i>
                            <h4>Payment Successful!</h4>
                            <p>Your payment has been received and processed successfully.</p>
                        </div>
                        <a href="<?php echo site_url('students/fees/' . $payment->student_id); ?>" class="btn btn-primary">
                            View Fee Details
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="text-center mt-4">
                        <div class="alert alert-danger">
                            <i class="fa fa-times-circle fa-3x mb-3"></i>
                            <h4>Payment Failed</h4>
                            <p>There was an issue processing your payment.</p>
                        </div>
                        <a href="<?php echo site_url('mpesa'); ?>" class="btn btn-primary">
                            Try Again
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($payment->status == 'pending'): ?>
<script>
    // Refresh the page every 10 seconds to check for payment status updates
    setTimeout(function() {
        location.reload();
    }, 10000);
</script>
<?php endif; ?>