<?php
$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collect Fees</title>
    <style type="text/css">
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
        }

        .form-group input[type="radio"] {
            width: auto;
            margin-right: 5px;
        }

        .payment-details {
            margin-top: 20px;
        }

        .payment-details p {
            margin: 5px 0;
        }

        .total {
            font-weight: bold;
        }

        button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        #phoneNumberField, #mpesaAmountField {
            display: none;
        }

        .radio-inline {
            margin-right: 15px;
        }

        .pull-right {
            float: right;
        }

        .text-danger {
            color: #dc3545;
        }

        .product-info {
            margin-left: 0;
        }

        .product-title {
            font-weight: 600;
            font-size: 15px;
            display: inline;
        }

        .product-description {
            display: block;
            color: #999;
        }

        /* Fix label overflow and ensure radio buttons stay in one line */
        .payment-mode-container {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 5px;
        }

        .payment-mode-container .radio-inline {
            margin-right: 15px;
            display: inline-flex;
            align-items: center;
        }

        /* Ensure the card container doesn't overflow */
        .card-container {
            max-width: 100%;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="container card-container">
        <h2>Collect Fees</h2>
        <?php echo form_open('payment/process', array('class' => 'form-horizontal')); ?>
        <div class="col-lg-12">
            <div class="form-group row">
                <label for="inputEmail3" class="col-sm-3 control-label"><?php echo $this->lang->line('date'); ?> <small class="req"> *</small></label>
                <div class="col-sm-9">
                    <input id="date" name="collected_date" placeholder="" type="text" class="form-control date_fee" value="" readonly="readonly" autocomplete="off">
                    <span id="form_collection_collected_date_error" class="text text-danger"></span>
                </div>
            </div>

            <div class="form-group row">
                <label for="inputPassword3" class="col-sm-3 control-label"><?php echo $this->lang->line('payment_mode'); ?></label>
                <div class="col-sm-9">
                    <div class="payment-mode-container">
                        <label class="radio-inline">
                            <input type="radio" name="payment_mode_fee" value="Cash" checked="checked"> <?php echo $this->lang->line('cash'); ?>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="payment_mode_fee" value="Cheque"> <?php echo $this->lang->line('cheque'); ?>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="payment_mode_fee" value="DD"><?php echo $this->lang->line('dd'); ?>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="payment_mode_fee" value="bank_transfer"><?php echo $this->lang->line('bank_transfer'); ?>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="payment_mode_fee" value="upi"><?php echo $this->lang->line('upi'); ?>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="payment_mode_fee" value="card"><?php echo $this->lang->line('card'); ?>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" id="mpesa" name="payment_mode_fee" value="M-PESA"> <?php echo 'M-PESA'; ?>
                        </label>
                    </div>
                    <span class="text-danger" id="payment_mode_error"></span>
                    <span id="form_collection_payment_mode_fee_error" class="text text-danger"></span>
                </div>
            </div>

            <!-- Add phone number field for M-PESA -->
            <div class="form-group row" id="phoneNumberField">
                <label for="phone_number" class="col-sm-3 control-label">Phone Number (e.g., 2547XXXXXXXX) *</label>
                <div class="col-sm-9">
                    <input type="text" id="phone_number" name="phone_number" placeholder="2547XXXXXXXX" class="form-control">
                </div>
            </div>

            <!-- Add amount field for M-PESA -->
            <div class="form-group row" id="mpesaAmountField">
                <label for="mpesa_amount" class="col-sm-3 control-label">Amount *</label>
                <div class="col-sm-9">
                    <input type="number" id="mpesa_amount" name="mpesa_amount" placeholder="Enter amount to pay" class="form-control" step="0.01" min="0">
                </div>
            </div>

            <div class="form-group row">
                <label for="inputPassword3" class="col-sm-3 control-label"><?php echo $this->lang->line('note') ?></label>
                <div class="col-sm-9">
                    <textarea class="form-control" rows="3" name="fee_gupcollected_note" id="description" placeholder=""></textarea>
                    <span id="form_collection_fee_gupcollected_note_error" class="text text-danger"></span>
                </div>
            </div>

            <!-- Hidden fields for M-PESA -->
            <input type="hidden" name="student_fees_master_id" value="<?php echo isset($student_fees_master_id) ? $student_fees_master_id : '1'; ?>">
            <input type="hidden" name="student_session_id" value="<?php echo isset($student_session_id) ? $student_session_id : '1'; ?>">
            <input type="hidden" name="amount" value="<?php echo isset($total_amount) ? $total_amount : '0'; ?>">
            <input type="hidden" name="invoice_id" value="<?php echo isset($invoice_id) ? $invoice_id : 'INV-' . time(); ?>">
        </div>

        <div class="payment-details">
            <?php
            $row_counter = 1;
            $total_amount = 0;
            foreach ($feearray as $fee_key => $fee_value) {
                $amount_prev_paid = 0;
                $fees_fine_amount = 0;
                $fine_amount_paid = 0;
                $fine_amount_status = false;

                if ($fee_value->fee_category == "fees") {
                    $amount_to_be_pay = $fee_value->amount;

                    if ($fee_value->is_system) {
                        $amount_to_be_pay = $fee_value->student_fees_master_amount;
                    }

                    if (is_string(($fee_value->amount_detail)) && is_array(json_decode(($fee_value->amount_detail), true))) {
                        $amount_data = json_decode($fee_value->amount_detail);
                        foreach ($amount_data as $amount_data_key => $amount_data_value) {
                            $fine_amount_paid += $amount_data_value->amount_fine;
                            $amount_prev_paid = $amount_prev_paid + ($amount_data_value->amount + $amount_data_value->amount_discount);
                        }

                        if ($fee_value->is_system) {
                            $amount_to_be_pay = $fee_value->student_fees_master_amount - $amount_prev_paid;
                        } else {
                            $amount_to_be_pay = $fee_value->amount - $amount_prev_paid;
                        }
                    }

                    if (($fee_value->due_date != "0000-00-00" && $fee_value->due_date != NULL) && (strtotime($fee_value->due_date) < strtotime(date('Y-m-d'))) && $amount_to_be_pay > 0) {
                        $fees_fine_amount = $fee_value->fine_amount - $fine_amount_paid;
                        $total_amount = $total_amount + $fees_fine_amount;
                        $fine_amount_status = true;
                    }

                    $total_amount = $total_amount + $amount_to_be_pay;
                    if ($amount_to_be_pay > 0) {
                        ?>
                        <div class="product-info">
                            <input name="row_counter[]" type="hidden" value="<?php echo $row_counter; ?>">
                            <input name="student_fees_master_id_<?php echo $row_counter; ?>" type="hidden" value="<?php echo $fee_value->id; ?>">
                            <input name="fee_groups_feetype_id_<?php echo $row_counter; ?>" type="hidden" value="<?php echo $fee_value->fee_groups_feetype_id; ?>">
                            <input name="fee_groups_feetype_fine_amount_<?php echo $row_counter; ?>" type="hidden" value="<?php echo $fees_fine_amount; ?>">
                            <input name="fee_amount_<?php echo $row_counter; ?>" type="hidden" value="<?php echo $amount_to_be_pay; ?>">
                            <input name="fee_category_<?php echo $row_counter; ?>" type="hidden" value="<?php echo $fee_value->fee_category; ?>">
                            <input name="trans_fee_id_<?php echo $row_counter; ?>" type="hidden" value="0">
                            <p>
                                <span class="product-title">
                                    <?php
                                    if ($fee_value->is_system) {
                                        echo $this->lang->line($fee_value->name) . " (" . $this->lang->line($fee_value->type) . ")";
                                    } else {
                                        echo $fee_value->name . " (" . $fee_value->type . ")";
                                    }
                                    ?>
                                </span>
                                <span class="pull-right"><?php echo $currency_symbol . amountFormat((float) $amount_to_be_pay, 2, '.', ''); ?></span>
                            </p>
                            <p class="product-description">
                                <?php
                                if ($fee_value->is_system) {
                                    echo $this->lang->line($fee_value->code);
                                } else {
                                    echo $fee_value->code;
                                }
                                ?>
                            </p>
                            <?php if ($fine_amount_status) { ?>
                                <p class="text-danger">
                                    <?php echo $this->lang->line('fine'); ?>
                                    <span class="pull-right"><?php echo $currency_symbol . amountFormat((float) $fees_fine_amount, 2, '.', ''); ?></span>
                                </p>
                            <?php } ?>
                        </div>
                        <?php
                    }
                } elseif ($fee_value->fee_category == "transport") {
                    $amount_to_be_pay = $fee_value->fees;

                    if (is_string(($fee_value->amount_detail)) && is_array(json_decode(($fee_value->amount_detail), true))) {
                        $amount_data = json_decode($fee_value->amount_detail);
                        foreach ($amount_data as $amount_data_key => $amount_data_value) {
                            $fine_amount_paid += $amount_data_value->amount_fine;
                            $amount_prev_paid = $amount_prev_paid + ($amount_data_value->amount + $amount_data_value->amount_discount);
                        }
                        $amount_to_be_pay = $fee_value->fees - $amount_prev_paid;
                    }

                    if (($fee_value->due_date != "0000-00-00" && $fee_value->due_date != NULL) && (strtotime($fee_value->due_date) < strtotime(date('Y-m-d'))) && $amount_to_be_pay > 0) {
                        $transport_fine_amount = is_null($fee_value->fine_percentage) ? $fee_value->fine_amount : percentageAmount($fee_value->fees, $fee_value->fine_percentage);
                        $fees_fine_amount = $transport_fine_amount - $fine_amount_paid;
                        $total_amount = $total_amount + $fees_fine_amount;
                        $fine_amount_status = true;
                    }

                    $total_amount = $total_amount + $amount_to_be_pay;
                    if ($amount_to_be_pay > 0) {
                        ?>
                        <div class="product-info">
                            <input name="row_counter[]" type="hidden" value="<?php echo $row_counter; ?>">
                            <input name="student_fees_master_id_<?php echo $row_counter; ?>" type="hidden" value="0">
                            <input name="fee_groups_feetype_id_<?php echo $row_counter; ?>" type="hidden" value="0">
                            <input name="fee_groups_feetype_fine_amount_<?php echo $row_counter; ?>" type="hidden" value="<?php echo $fees_fine_amount; ?>">
                            <input name="fee_amount_<?php echo $row_counter; ?>" type="hidden" value="<?php echo $amount_to_be_pay; ?>">
                            <input name="fee_category_<?php echo $row_counter; ?>" type="hidden" value="<?php echo $fee_value->fee_category; ?>">
                            <input name="trans_fee_id_<?php echo $row_counter; ?>" type="hidden" value="<?php echo $fee_value->id; ?>">
                            <p>
                                <span class="product-title"><?php echo $this->lang->line("transport_fees") ?></span>
                                <span class="pull-right"><?php echo $currency_symbol . amountFormat((float) $amount_to_be_pay, 2, '.', ''); ?></span>
                            </p>
                            <p class="product-description">
                                <?php echo $fee_value->month; ?>
                            </p>
                            <?php if ($fine_amount_status) { ?>
                                <p class="text-danger">
                                    <?php echo $this->lang->line('fine'); ?>
                                    <span class="pull-right"><?php echo $currency_symbol . amountFormat((float) $fees_fine_amount, 2, '.', ''); ?></span>
                                </p>
                            <?php } ?>
                        </div>
                        <?php
                    }
                }
                $row_counter++;
            }
            ?>
            <?php if ($total_amount > 0) { ?>
                <p class="total">
                    <?php echo $this->lang->line('total_pay'); ?>
                    <span class="pull-right"><?php echo $currency_symbol . amountFormat((float) $total_amount, 2, '.', ''); ?></span>
                </p>
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary pull-right payment_collect" data-loading-text="<i class='fa fa-spinner fa-spin '></i><?php echo $this->lang->line('processing')?>">
                            <i class="fa fa-money"></i> <?php echo $this->lang->line('pay'); ?>
                        </button>
                    </div>
                </div>
            <?php } else { ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info mb0">
                            <?php echo $this->lang->line('no_fees_found'); ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

        <?php echo form_close(); ?>
    </div>

    <script>
        const paymentModeRadios = document.querySelectorAll('input[name="payment_mode_fee"]');
        const phoneNumberField = document.getElementById('phoneNumberField');
        const phoneNumberInput = document.getElementById('phone_number');
        const mpesaAmountField = document.getElementById('mpesaAmountField');
        const mpesaAmountInput = document.getElementById('mpesa_amount');

        paymentModeRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                if (radio.value === 'M-PESA') {
                    phoneNumberField.style.display = 'block';
                    phoneNumberInput.setAttribute('required', 'required');
                    mpesaAmountField.style.display = 'block';
                    mpesaAmountInput.setAttribute('required', 'required');
                } else {
                    phoneNumberField.style.display = 'none';
                    phoneNumberInput.removeAttribute('required');
                    mpesaAmountField.style.display = 'none';
                    mpesaAmountInput.removeAttribute('required');
                }
            });
        });
    </script>
</body>
</html>