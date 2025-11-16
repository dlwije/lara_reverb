import React from 'react';

const PaymentPending = ({ message, reference }) => {
    return (
        <div className="min-h-screen bg-blue-50 flex items-center justify-center px-4">
            <div className="max-w-md w-full bg-white rounded-lg shadow-lg p-6 text-center">
                <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg className="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h2 className="text-2xl font-bold text-gray-900 mb-2">Payment Processing</h2>
                <p className="text-gray-600 mb-4">{message}</p>
                {reference && (
                    <p className="text-sm text-gray-500 mb-4">
                        Reference: <span className="font-mono">{reference}</span>
                    </p>
                )}
                <div className="animate-pulse text-blue-600 mb-4">
                    Processing your payment...
                </div>
                <a href="/" className="inline-block bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                    Return Home
                </a>
            </div>
        </div>
    );
};

export default PaymentPending;
