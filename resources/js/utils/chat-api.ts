import axios, { AxiosResponse } from 'axios'

interface BackendMessage {
    id: number
    user_id: number
    from: number
    message: string
    created_at: string
    updated_at: string
    isUser: boolean
}

interface ApiResponse {
    status: boolean
    message: string
    data: BackendMessage[]
}

interface SendMessagePayload {
    user_id: number
    from: number
    message: string
}

interface SendMessageResponse {
    status: boolean
    message: string
    data: BackendMessage
}

// Create axios instance with default config
const apiClient = axios.create({
    baseURL: import.meta.env.APP_URL || '',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
})

// Add request interceptor for authentication if needed
apiClient.interceptors.request.use(
    (config) => {
        // Add auth token if available
        const token = localStorage.getItem('acc_token')
        // console.log('chat_api:'+token)
        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        }
        return config
    },
    (error) => {
        return Promise.reject(error)
    }
)

// Add response interceptor for error handling
apiClient.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Handle unauthorized access
            localStorage.removeItem('acc_token')
            // Redirect to login if needed
        }
        return Promise.reject(error)
    }
)

// Utility function to fetch messages from your backend
export async function fetchMessages(userId: number): Promise<BackendMessage[]> {
    try {
        console.log('🔄 Fetching messages for user ID:', userId)
        const response: AxiosResponse<ApiResponse> = await apiClient.get(`/api/v1/get-conversation/${userId}`)

        console.log('📥 API Response:', response.data)

        if (!response.data.status) {
            throw new Error(response.data.message || 'Failed to fetch messages')
        }


        console.log('✅ Successfully fetched', response.data.data.length, 'messages')
        return response.data.data
    } catch (error) {
        if (axios.isAxiosError(error)) {
            console.error('Axios error fetching messages:', {
                message: error.message,
                status: error.response?.status,
                data: error.response?.data
            })

            if (error.response?.status === 404) {
                console.warn('Messages endpoint not found - using empty array')
                return []
            }

            if (error.code === 'ECONNABORTED') {
                throw new Error('Request timeout - please check your connection')
            }

            throw new Error(error.response?.data?.message || 'Failed to fetch messages')
        }

        console.error('Unexpected error fetching messages:', error)
        return []
    }
}

// Utility function to send a message to your backend
export async function sendMessageToBackend(payload: SendMessagePayload): Promise<BackendMessage | null> {
    try {
        console.log('📤 Sending message:', payload)
        const response: AxiosResponse<SendMessageResponse> = await apiClient.post(route('api.v1.conversation.store'), payload)

        console.log('📤 Send message response:', response.data)

        if (!response.data.status) {
            throw new Error(response.data.message || 'Failed to send message')
        }

        return response.data.data
    } catch (error) {
        if (axios.isAxiosError(error)) {
            console.error('Axios error sending message:', {
                message: error.message,
                status: error.response?.status,
                data: error.response?.data
            })

            if (error.code === 'ECONNABORTED') {
                throw new Error('Request timeout - please try again')
            }

            throw new Error(error.response?.data?.message || 'Failed to send message')
        }

        console.error('Unexpected error sending message:', error)
        return null
    }
}

// Utility function to determine if message is from current user
export function determineIsUser(message: BackendMessage, currentUserId: number): BackendMessage {
    return {
        ...message,
        isUser: message.from === currentUserId
    }
}

// Utility function to sort messages by creation date
export function sortMessagesByDate(messages: BackendMessage[]): BackendMessage[] {
    return messages.sort((a, b) =>
        new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
    )
}

// Utility function to cancel ongoing requests
export function cancelRequest(source: any) {
    if (source) {
        source.cancel('Request canceled by user')
    }
}

// Create cancel token source for requests
export function createCancelTokenSource() {
    return axios.CancelToken.source()
}
