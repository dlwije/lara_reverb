import React from 'react';

const PaymentDecline = ({ message, details, reference }) => {
    return (
        <div className="min-h-screen bg-red-50 flex items-center justify-center px-4">
            <div className="max-w-md w-full bg-white rounded-lg shadow-lg p-6 text-center">
                <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg className="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <h2 className="text-2xl font-bold text-gray-900 mb-2">Payment Declined</h2>
                <p className="text-gray-600 mb-4">{message}</p>
                {reference && (
                    <p className="text-sm text-gray-500 mb-2">
                        Reference: <span className="font-mono">{reference}</span>
                    </p>
                )}
                <a href="/payment" className="inline-block bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700 transition-colors mr-2">
                    Try Again
                </a>
                <a href="/" className="inline-block bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700 transition-colors">
                    Return Home
                </a>
            </div>
        </div>
    );
};

export default PaymentDecline;
