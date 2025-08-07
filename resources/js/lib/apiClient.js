import axios from 'axios'

const apiClient = axios.create({
    baseURL: import.meta.env.APP_URL || '',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
})

// Request Interceptor: add auth token
apiClient.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('acc_token')
        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        }
        return config
    },
    (error) => Promise.reject(error)
)

// Response Interceptor: handle 401
apiClient.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('acc_token')
            // Optionally redirect to login
        }
        return Promise.reject(error)
    }
)

export default apiClient
