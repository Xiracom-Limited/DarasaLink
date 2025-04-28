<?php 
$is_dashboard = true;
$page_title = 'M-Pesa Integration';
$breadcrumbs = array('M-Pesa' => false);
$load_datatables = true;
?>

<?php $this->load->view('header'); ?>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">M-Pesa Configuration</h5>
            </div>
            <div class="card-body">
                <?php echo form_open('mpesa/save_config'); ?>
                    <div class="mb-3">
                        <label for="env" class="form-label">Environment</label>
                        <select name="env" id="env" class="form-select">
                            <option value="sandbox" <?php echo (isset($config->env) && $config->env == 'sandbox') ? 'selected' : ''; ?>>Sandbox</option>
                            <option value="live" <?php echo (isset($config->env) && $config->env == 'live') ? 'selected' : ''; ?>>Live</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="type" class="form-label">Integration Type</label>
                        <select name="type" id="type" class="form-select">
                            <option value="4" <?php echo (isset($config->type) && $config->type == 4) ? 'selected' : ''; ?>>PayBill</option>
                            <option value="2" <?php echo (isset($config->type) && $config->type == 2) ? 'selected' : ''; ?>>Buy Goods</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="shortcode" class="form-label">Shortcode</label>
                        <input type="text" class="form-control" id="shortcode" name="shortcode" value="<?php echo isset($config->shortcode) ? $config->shortcode : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="key" class="form-label">Consumer Key</label>
                        <input type="text" class="form-control" id="key" name="key" value="<?php echo isset($config->key) ? $config->key : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="secret" class="form-label">Consumer Secret</label>
                        <input type="text" class="form-control" id="secret" name="secret" value="<?php echo isset($config->secret) ? $config->secret : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="passkey" class="form-label">Passkey</label>
                        <input type="text" class="form-control" id="passkey" name="passkey" value="<?php echo isset($config->passkey) ? $config->passkey : ''; ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Configuration</button>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">API Registration</h5>
            </div>
            <div class="card-body">
                <p>Register your M-Pesa callback URLs to receive payment notifications from Safaricom.</p>
                
                <div class="mb-3">
                    <label class="form-label">Validation URL</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo base_url('mpesa/validate'); ?>" readonly>
                        <button class="btn btn-outline-secondary copy-btn" type="button" data-clipboard-text="<?php echo base_url('mpesa/validate'); ?>">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Confirmation URL</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo base_url('mpesa/confirm'); ?>" readonly>
                        <button class="btn btn-outline-secondary copy-btn" type="button" data-clipboard-text="<?php echo base_url('mpesa/confirm'); ?>">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Reconciliation URL</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo base_url('mpesa/reconcile'); ?>" readonly>
                        <button class="btn btn-outline-secondary copy-btn" type="button" data-clipboard-text="<?php echo base_url('mpesa/reconcile'); ?>">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                
                <a href="<?php echo base_url('mpesa/register_urls'); ?>" class="btn btn-success">Register URLs</a>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Test Payment</h5>
            </div>
            <div class="card-body">
                <?php echo form_open('mpesa/test_payment'); ?>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="e.g. 254712345678" required>
                        <small class="form-text text-muted">Enter a Kenyan phone number starting with 254.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="number" class="form-control" id="amount" name="amount" value="1" min="1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reference" class="form-label">Reference</label>
                        <input type="text" class="form-control" id="reference" name="reference" value="TEST" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Initiate Test Payment</button>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Recent Transactions</h5>
    </div>
    <div class="card-body">
        <table class="table table-striped datatable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Phone</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>M-Pesa Reference</th>
                </tr>
            </thead>
            <tbody>
                <?php if(isset($transactions) && is_array($transactions)): ?>
                    <?php foreach($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo isset($transaction->created_at) ? date('Y-m-d H:i', strtotime($transaction->created_at)) : '-'; ?></td>
                            <td><?php echo isset($transaction->bill_ref_number) ? $transaction->bill_ref_number : '-'; ?></td>
                            <td><?php echo isset($transaction->msisdn) ? $transaction->msisdn : '-'; ?></td>
                            <td><?php echo isset($transaction->trans_amount) ? number_format($transaction->trans_amount, 2) : '-'; ?></td>
                            <td>
                                <?php if(isset($transaction->status)): ?>
                                    <?php if($transaction->status == 'completed'): ?>
                                        <span class="badge bg-success">Completed</span>
                                    <?php elseif($transaction->status == 'pending'): ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php elseif($transaction->status == 'failed'): ?>
                                        <span class="badge bg-danger">Failed</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo ucfirst($transaction->status); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Unknown</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo isset($transaction->trans_id) ? $transaction->trans_id : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No transactions found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation for phone number
    document.querySelector('form').addEventListener('submit', function(e) {
        const phoneInput = document.getElementById('phone');
        let phone = phoneInput.value.trim();
        
        // Ensure phone starts with 254
        if (!phone.startsWith('254')) {
            // If it starts with 0, replace with 254
            if (phone.startsWith('0')) {
                phone = '254' + phone.substring(1);
            } else if (phone.startsWith('+254')) {
                phone = phone.substring(1); // Remove the +
            } else {
                phone = '254' + phone;
            }
            phoneInput.value = phone;
        }
    });
});
</script>

<?php $this->load->view('footer'); ?>