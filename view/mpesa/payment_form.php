<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4>M-Pesa Payment</h4>
                </div>
                <div class="card-body">
                    <?php if($this->session->flashdata('error')): ?>
                        <div class="alert alert-danger">
                            <?php echo $this->session->flashdata('error'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php echo form_open('mpesa/process'); ?>
                    
                    <div class="form-group">
                        <label for="student_id">Select Student</label>
                        <select name="student_id" id="student_id" class="form-control" required>
                            <option value="">-- Select Student --</option>
                            <?php foreach($students as $student): ?>
                                <option value="<?php echo $student->id; ?>">
                                    <?php echo $student->admission_no . ' - ' . $student->firstname . ' ' . $student->lastname; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php echo form_error('student_id', '<div class="text-danger">', '</div>'); ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="fee_groups_feetype_id">Select Fee Type</label>
                        <select name="fee_groups_feetype_id" id="fee_groups_feetype_id" class="form-control" required>
                            <option value="">-- Select Fee Type --</option>
                            <!-- Fee types will be populated via AJAX based on student selection -->
                        </select>
                        <?php echo form_error('fee_groups_feetype_id', '<div class="text-danger">', '</div>'); ?>
                        <input type="hidden" name="feetype_id" id="feetype_id">
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">Amount (KES)</label>
                        <input type="number" name="amount" id="amount" class="form-control" min="1" step="0.01" required>
                        <?php echo form_error('amount', '<div class="text-danger">', '</div>'); ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number (M-Pesa)</label>
                        <input type="text" name="phone" id="phone" class="form-control" placeholder="e.g., 0712345678" required>
                        <small class="text-muted">This is the phone number that will receive the STK push notification.</small>
                        <?php echo form_error('phone', '<div class="text-danger">', '</div>'); ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_purpose">Payment Purpose</label>
                        <select name="payment_purpose" id="payment_purpose" class="form-control" required>
                            <option value="fees">School Fees</option>
                            <option value="admission">Admission Fee</option>
                            <option value="uniform">Uniform</option>
                            <option value="transport">Transport</option>
                            <option value="other">Other</option>
                        </select>
                        <?php echo form_error('payment_purpose', '<div class="text-danger">', '</div>'); ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="academic_year">Academic Year</label>
                        <select name="academic_year" id="academic_year" class="form-control" required>
                            <option value="">-- Select Academic Year --</option>
                            <option value="2024-2025" selected>2024-2025</option>
                            <option value="2025-2026">2025-2026</option>
                        </select>
                        <?php echo form_error('academic_year', '<div class="text-danger">', '</div>'); ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="term">Term</label>
                        <select name="term" id="term" class="form-control" required>
                            <option value="">-- Select Term --</option>
                            <option value="Term 1">Term 1</option>
                            <option value="Term 2" selected>Term 2</option>
                            <option value="Term 3">Term 3</option>
                        </select>
                        <?php echo form_error('term', '<div class="text-danger">', '</div>'); ?>
                    </div>
                    
                    <input type="hidden" name="session_id" value="21"><!-- Current session ID -->
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary btn-block">
                            Pay with M-Pesa
                        </button>
                    </div>
                    
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // When student is selected, fetch fee types
    $('#student_id').change(function() {
        var studentId = $(this).val();
        if (studentId) {
            $.ajax({
                url: '<?php echo site_url("fees/get_student_fee_types/"); ?>' + studentId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#fee_groups_feetype_id').empty();
                    $('#fee_groups_feetype_id').append('<option value="">-- Select Fee Type --</option>');
                    
                    $.each(data, function(key, value) {
                        $('#fee_groups_feetype_id').append('<option value="' + value.fee_groups_feetype_id + '" data-feetype="' + value.feetype_id + '" data-amount="' + value.amount + '">' + value.type + ' (' + value.amount + ' KES)</option>');
                    });
                }
            });
        } else {
            $('#fee_groups_feetype_id').empty();
            $('#fee_groups_feetype_id').append('<option value="">-- Select Fee Type --</option>');
        }
    });
    
    // When fee type is selected, update amount and feetype ID
    $('#fee_groups_feetype_id').change(function() {
        var selectedOption = $(this).find('option:selected');
        var amount = selectedOption.data('amount');
        var feetypeId = selectedOption.data('feetype');
        
        if (amount) {
            $('#amount').val(amount);
        }
        
        if (feetypeId) {
            $('#feetype_id').val(feetypeId);
        }
    });
});
</script>