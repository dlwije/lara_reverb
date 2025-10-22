import React from 'react';

const PaymentSuccess = ({ message, reference, transaction_ref }) => {
    return (
        <div className="min-h-screen bg-green-50 flex items-center justify-center px-4">
            <div className="max-w-md w-full bg-white rounded-lg shadow-lg p-6 text-center">
                <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg className="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 className="text-2xl font-bold text-gray-900 mb-2">Payment Successful</h2>
                <p className="text-gray-600 mb-4">{message}</p>
                {reference && (
                    <p className="text-sm text-gray-500 mb-2">
                        Reference: <span className="font-mono">{reference}</span>
                    </p>
                )}
                {transaction_ref && (
                    <p className="text-sm text-gray-500 mb-4">
                        Transaction: <span className="font-mono">{transaction_ref}</span>
                    </p>
                )}
                <a href="/" className="inline-block bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 transition-colors">
                    Return Home
                </a>
            </div>
        </div>
    );
};

export default PaymentSuccess;
