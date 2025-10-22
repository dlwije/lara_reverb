import React, { useState, useEffect, useRef } from 'react';
import { useForm } from '@inertiajs/react';

const TelrPayment = ({ redirect_data }) => {
    const [telrToken, setTelrToken] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [paymentUrl, setPaymentUrl] = useState('');
    const [showIframe, setShowIframe] = useState(false);
    const [iframeKey, setIframeKey] = useState(0); // Force iframe re-render
    const iframeRef = useRef(null);

    const { data, setData, post, processing } = useForm({
        telr_token: '',
        store_id: '28454',
        auth_key: 'kxSxd-H2Bt#ZhMGP',
        amount: '10',
        currency: 'AED',
        bill_fname: 'John Lara',
        bill_sname: 'Doe',
        bill_addr1: 'Test Address Line 1',
        bill_addr2: 'Test Address Line 2',
        bill_city: 'Mumbai',
        bill_region: 'Maharashtra',
        bill_zip: '400001',
        bill_country: 'India',
        bill_email: 'support@telr.com',
        bill_tel: '9820098200',
        repeat_amount: '20',
        repeat_final: '30',
        repeat_interval: '1',
        repeat_period: 'M',
        repeat_term: '5'
    });

    // Handle redirect data from server
    useEffect(() => {
        if (redirect_data && redirect_data.redirect_link) {
            setPaymentUrl(redirect_data.redirect_link);
            setShowIframe(true);
            setIframeKey(prev => prev + 1); // Force iframe re-render
        }
    }, [redirect_data]);

    // Initialize Telr SDK
    useEffect(() => {
        const script = document.createElement('script');
        script.src = 'https://secure.telr.com/jssdk/v2/telr_sdk.js';
        script.async = true;
        document.body.appendChild(script);

        script.onload = () => {
            const onTokenReceive = (token) => {
                console.log('Telr token received:', token);
                setTelrToken(token);
                setData('telr_token', token);
            };

            const telr_params = {
                store_id: data.store_id,
                currency: data.currency,
                test_mode: 1,
                appearance: {
                    labels: 1,
                    logos: 1,
                    borders: 1,
                    dropdowns: 0
                },
                callback: onTokenReceive
            };

            if (window.telrSdk) {
                window.telrSdk.init(telr_params);
            }
        };

        script.onerror = () => {
            console.error('Failed to load Telr SDK');
            alert('Failed to load payment system. Please refresh the page.');
        };

        return () => {
            if (document.body.contains(script)) {
                document.body.removeChild(script);
            }
        };
    }, [data.store_id, data.currency]);

    // Handle iframe navigation and messages
    useEffect(() => {
        const handleIframeNavigation = (event) => {
            // Check if the navigation is from our iframe
            if (iframeRef.current && iframeRef.current.contentWindow === event.source) {
                console.log('Iframe navigation detected:', event);

                // Handle different URL patterns for success/failure
                if (event.data && event.data.type === 'payment_completed') {
                    handlePaymentCompletion(event.data);
                }
            }
        };

        // Listen for beforeunload to handle iframe navigation
        const handleBeforeUnload = () => {
            // This might be triggered when iframe tries to navigate
            console.log('Page unload detected - might be iframe navigation');
        };

        window.addEventListener('message', handleIframeNavigation);
        window.addEventListener('beforeunload', handleBeforeUnload);

        return () => {
            window.removeEventListener('message', handleIframeNavigation);
            window.removeEventListener('beforeunload', handleBeforeUnload);
        };
    }, [showIframe]);

    const handlePaymentCompletion = (data) => {
        console.log('Payment completion data:', data);
        // Handle successful payment
        // You might want to redirect to a success page or show a success message
        window.location.href = '/telr/success';
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!telrToken) {
            alert("Please complete the card details in the payment form");
            return;
        }

        setIsSubmitting(true);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch('telr/process-payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
                body: JSON.stringify({
                    ...data,
                    telr_token: telrToken
                })
            });

            const result = await response.json();
            console.log('Payment response:', result);

            if (result.success && result.redirect_link) {
                setPaymentUrl(result.redirect_link);
                setShowIframe(true);
                setIframeKey(prev => prev + 1); // Force iframe re-render

                // Scroll to iframe smoothly
                setTimeout(() => {
                    document.getElementById('payment-iframe-section')?.scrollIntoView({
                        behavior: 'smooth'
                    });
                }, 300);
            } else {
                alert(result.error || 'Payment processing failed');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while processing payment');
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleInputChange = (field, value) => {
        setData(field, value);
    };

    const handleIframeLoad = () => {
        console.log('Payment iframe loaded successfully');

        // Try to inject missing scripts or handle errors
        try {
            const iframe = iframeRef.current;
            if (iframe && iframe.contentDocument) {
                console.log('Iframe document loaded');
            }
        } catch (error) {
            console.log('Cannot access iframe document due to cross-origin restrictions');
        }
    };

    const handleIframeError = () => {
        console.error('Failed to load payment iframe');
        alert('Failed to load payment gateway. Please try again.');
        setShowIframe(false);
    };

    // Alternative: Open in new window instead of iframe
    const openInNewWindow = () => {
        if (paymentUrl) {
            const newWindow = window.open(paymentUrl, 'telr_payment', 'width=800,height=600,scrollbars=yes');
            if (newWindow) {
                // Poll to check when the window closes or navigates
                const checkWindow = setInterval(() => {
                    if (newWindow.closed) {
                        clearInterval(checkWindow);
                        // Check payment status when window closes
                        checkPaymentStatus();
                    }
                }, 1000);
            }
        }
    };

    const checkPaymentStatus = () => {
        // Make an API call to check payment status
        fetch('telr/check-payment-status')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = '/telr/success';
                } else if (data.status === 'failed') {
                    alert('Payment failed. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error checking payment status:', error);
            });
    };

    const resetPayment = () => {
        setShowIframe(false);
        setPaymentUrl('');
        setTelrToken('');
        // Reset Telr frame
        const telrFrame = document.getElementById('telr_frame');
        if (telrFrame) {
            telrFrame.innerHTML = '';
        }
        // Reinitialize Telr SDK
        if (window.telrSdk) {
            window.telrSdk.init({
                store_id: data.store_id,
                currency: data.currency,
                test_mode: 1,
                appearance: {
                    labels: 1,
                    logos: 1,
                    borders: 1,
                    dropdowns: 0
                },
                callback: (token) => {
                    setTelrToken(token);
                    setData('telr_token', token);
                }
            });
        }
    };

    return (
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div className="text-center mb-8">
                <h1 className="text-3xl font-bold text-gray-900 mb-2">Secure Payment</h1>
                <p className="text-gray-600">Complete your transaction securely</p>
            </div>

            <div className="grid grid-cols-1 gap-8">
                {!showIframe ? (
                    // Payment Form
                    <form onSubmit={handleSubmit} className="space-y-8 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <input type="hidden" name="telr_token" value={telrToken} />

                        {/* Store & Amount Section */}
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div className="space-y-6">
                                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <h3 className="text-lg font-semibold text-blue-900 mb-3">Store Information</h3>
                                    <div className="space-y-4">
                                        <div>
                                            <label htmlFor="store_id" className="block text-sm font-medium text-gray-700 mb-2">
                                                Store ID *
                                            </label>
                                            <input
                                                type="text"
                                                className="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                                name="store_id"
                                                id="store_id"
                                                value={data.store_id}
                                                onChange={(e) => handleInputChange('store_id', e.target.value)}
                                                required
                                            />
                                        </div>
                                        <div>
                                            <label htmlFor="auth_key" className="block text-sm font-medium text-gray-700 mb-2">
                                                Auth Key *
                                            </label>
                                            <input
                                                type="password"
                                                className="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                                name="auth_key"
                                                id="auth_key"
                                                value={data.auth_key}
                                                onChange={(e) => handleInputChange('auth_key', e.target.value)}
                                                required
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-6">
                                <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <h3 className="text-lg font-semibold text-green-900 mb-3">Payment Details</h3>
                                    <div className="space-y-4">
                                        <div>
                                            <label htmlFor="amount" className="block text-sm font-medium text-gray-700 mb-2">
                                                Amount *
                                            </label>
                                            <input
                                                type="text"
                                                className="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                                                name="amount"
                                                id="amount"
                                                value={data.amount}
                                                onChange={(e) => handleInputChange('amount', e.target.value)}
                                                required
                                            />
                                        </div>
                                        <div>
                                            <label htmlFor="currency" className="block text-sm font-medium text-gray-700 mb-2">
                                                Currency *
                                            </label>
                                            <input
                                                type="text"
                                                className="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                                                name="currency"
                                                id="currency"
                                                value={data.currency}
                                                onChange={(e) => handleInputChange('currency', e.target.value)}
                                                required
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Telr Card Frame */}
                        <div className="bg-white border border-gray-200 rounded-lg p-6">
                            <div className="mb-4">
                                <h3 className="text-xl font-semibold text-gray-900 mb-2">Card Details</h3>
                                <p className="text-gray-600">Enter your credit card information securely</p>
                            </div>
                            <div
                                id="telr_frame"
                                className="w-full min-h-48 border-2 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50 transition-colors hover:border-blue-300"
                            />
                            {!telrToken && (
                                <div className="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <p className="text-sm text-yellow-800 flex items-center">
                                        <svg className="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                        </svg>
                                        Please complete the card details above to proceed
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Submit Button */}
                        <div className="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                            <button
                                type="submit"
                                className="px-8 py-4 bg-blue-600 text-white font-semibold rounded-lg shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-105"
                                disabled={isSubmitting || !telrToken}
                            >
                                {isSubmitting ? (
                                    <span className="flex items-center justify-center">
                                        <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Processing Payment...
                                    </span>
                                ) : (
                                    <span className="flex items-center justify-center">
                                        <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                        Proceed to Secure Payment
                                    </span>
                                )}
                            </button>
                        </div>
                    </form>
                ) : (
                    // Payment Gateway Section with Iframe Alternative
                    <div id="payment-iframe-section" className="space-y-6">
                        <div className="text-center bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                            <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg className="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <h2 className="text-2xl font-bold text-gray-900 mb-2">Complete Your Payment</h2>
                            <p className="text-gray-600 mb-6">You are being redirected to our secure payment gateway</p>

                            {/* Iframe with relaxed sandbox for Telr compatibility */}
                            <div className="bg-white rounded-lg shadow-lg border border-gray-300 overflow-hidden mb-6">
                                <div className="bg-gray-800 text-white px-6 py-3">
                                    <h3 className="text-lg font-semibold flex items-center">
                                        <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                        </svg>
                                        Secure Payment Gateway
                                    </h3>
                                </div>
                                <iframe
                                    key={iframeKey}
                                    ref={iframeRef}
                                    src={paymentUrl}
                                    className="w-full h-96 md:h-120 lg:h-144"
                                    title="Secure Payment Gateway"
                                    onLoad={handleIframeLoad}
                                    onError={handleIframeError}
                                    sandbox="allow-forms allow-scripts allow-same-origin allow-popups allow-top-navigation"
                                    allow="payment *"
                                    style={{ minHeight: '600px' }}
                                />
                            </div>

                            {/* Alternative: Open in new window */}
                            <div className="text-center">
                                <p className="text-sm text-gray-600 mb-4">
                                    If the payment gateway is not loading properly, you can:
                                </p>
                                <div className="flex flex-col sm:flex-row gap-4 justify-center">
                                    <button
                                        onClick={openInNewWindow}
                                        className="px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200"
                                    >
                                        Open in New Window
                                    </button>
                                    <button
                                        onClick={resetPayment}
                                        className="px-6 py-3 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
                                    >
                                        ← Back to Payment Form
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default TelrPayment;
