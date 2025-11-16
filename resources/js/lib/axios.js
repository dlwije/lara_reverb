import Axios from 'axios';
import { useAnimation } from 'framer-motion';

const axios = Axios.create({
    baseURL: import.meta.env.APP_URL,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
    withXSRFToken: true,
});

// Set the Bearer auth token.
const setBearerToken = (token) => {
    // console.log('setToken:'+token);
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
};

function useMyAnimation() {
    // ✅ valid
    return useAnimation();
}

export { axios, setBearerToken, useMyAnimation };
