<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Payments - M-Pesa</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Payment Method Selection -->
    <div class="m-4">
        <h3 class="text-lg font-semibold mb-2">Select Payment Method:</h3>
        <button onclick="showModal()" class="bg-[#FFC600] text-black px-4 py-2 rounded-lg hover:bg-[#FFD700]">
            Pay with M-Pesa
        </button>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center">
        <div class="bg-white rounded-xl p-6 w-96 shadow-2xl">
            <!-- Main Payment Form -->
            <div id="paymentForm">
                <div class="flex items-center mb-4">
                    <img src="data:image/png;base64,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" 
                         alt="M-Pesa Logo" class="h-8 mr-2">
                    <h2 class="text-2xl font-bold text-gray-800">Student Payment</h2>
                </div>

                <form onsubmit="initiatePayment(event)">
                    <div class="mb-3">
                        <label class="block text-gray-700 mb-1 text-sm">PayBill Number</label>
                        <input type="number" id="paybill" 
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FFC600]"
                               value="888111" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="block text-gray-700 mb-1 text-sm">Student ID Number</label>
                        <input type="text" id="studentId" 
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FFC600]"
                               placeholder="Enter student admission number" required>
                    </div>

                    <div class="mb-3">
                        <label class="block text-gray-700 mb-1 text-sm">Parent's Phone Number</label>
                        <input type="tel" id="phone" 
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FFC600]"
                               placeholder="2547XX XXX XXX" required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 mb-1 text-sm">Amount (KES)</label>
                        <input type="number" id="amount" 
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#FFC600]"
                               placeholder="Enter tuition amount" required>
                    </div>

                    <div class="text-xs text-gray-500 mb-3">
                        You'll receive an M-Pesa prompt on your phone to complete payment
                    </div>

                    <button type="submit" 
                            class="w-full bg-[#FFC600] text-black py-2 rounded-lg hover:bg-[#FFD700] transition-colors font-medium">
                        Initiate M-Pesa Payment
                    </button>
                </form>
            </div>

            <!-- Loading Spinner -->
            <div id="loading" class="hidden flex-col items-center justify-center">
                <svg class="animate-spin h-12 w-12 text-[#FFC600]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-4 text-gray-600">Processing payment...</p>
            </div>

            <!-- Success State -->
            <div id="success" class="hidden text-center">
                <div class="text-green-500 text-6xl mb-4">✓</div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Payment Successful!</h3>
                <p class="text-gray-600">Your transaction has been completed.</p>
            </div>

            <!-- Error State -->
            <div id="error" class="hidden text-center">
                <div class="text-red-500 text-6xl mb-4">✗</div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Payment Failed</h3>
                <p class="text-gray-600" id="errorMessage"></p>
                <button onclick="resetModal()" 
                        class="mt-4 text-[#FFC600] hover:text-[#FFD700] font-medium">
                    Try Again
                </button>
            </div>
        </div>
    </div>

    <script>
        // Simulated SDK event listeners
        const sdkEvents = {
            onPending: () => showLoader(),
            onSuccess: () => showSuccess(),
            onFail: (error) => showError(error?.message || 'Payment failed')
        };

        function showModal() {
            document.getElementById('paymentModal').classList.remove('hidden');
        }

        function initiatePayment(e) {
            e.preventDefault();
            sdkEvents.onPending();
            
            // Simulate API call
            setTimeout(() => {
                if(Math.random() > 0.2) {
                    sdkEvents.onSuccess();
                } else {
                    sdkEvents.onFail({ message: 'Insufficient funds' });
                }
            }, 2000);
        }

        function showLoader() {
            document.getElementById('paymentForm').classList.add('hidden');
            document.getElementById('loading').classList.remove('hidden');
        }

        function showSuccess() {
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('success').classList.remove('hidden');
            
            // Send data to backend
            const paymentData = {
                paybill: document.getElementById('paybill').value,
                studentId: document.getElementById('studentId').value,
                phone: document.getElementById('phone').value,
                amount: document.getElementById('amount').value
            };
            console.log('Sending to backend:', paymentData);
        }

        function showError(message) {
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('errorMessage').textContent = message;
            document.getElementById('error').classList.remove('hidden');
        }

        function resetModal() {
            document.getElementById('error').classList.add('hidden');
            document.getElementById('paymentForm').classList.remove('hidden');
            document.getElementById('paymentModal').classList.add('hidden');
        }
    </script>
</body>
</html>