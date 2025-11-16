import React from 'react';

const PaymentCancel = ({ message }) => {
    return (
        <div className="min-h-screen bg-yellow-50 flex items-center justify-center px-4">
            <div className="max-w-md w-full bg-white rounded-lg shadow-lg p-6 text-center">
                <div className="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg className="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h2 className="text-2xl font-bold text-gray-900 mb-2">Payment Cancelled</h2>
                <p className="text-gray-600 mb-4">{message}</p>
                <a href="/payment" className="inline-block bg-yellow-600 text-white px-6 py-2 rounded-md hover:bg-yellow-700 transition-colors mr-2">
                    Try Again
                </a>
                <a href="/" className="inline-block bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700 transition-colors">
                    Return Home
                </a>
            </div>
        </div>
    );
};

export default PaymentCancel;
