"use client"

import { useState, useEffect } from "react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { AlertCircle, CheckCircle, XCircle, RefreshCw } from "lucide-react"
import apiClient from "@/lib/apiClient"

interface ApiConnectionTestProps {
    authToken?: string
    userId?: number
}

export function ApiConnectionTest({ authToken, userId }: ApiConnectionTestProps) {
    const [tests, setTests] = useState<any>({})
    const [running, setRunning] = useState(false)

    const runTests = async () => {
        setRunning(true)
        const results: any = {}

        // Test 1: Check authentication token
        results.authToken = {
            name: "Authentication Token",
            status: authToken ? "pass" : "fail",
            message: authToken ? "Token present" : "No auth token found",
            details: authToken ? `Token length: ${authToken.length}` : null,
        }

        // Test 2: Check localStorage token
        const localToken = typeof window !== "undefined" ? localStorage.getItem("acc_token") : null
        results.localStorage = {
            name: "LocalStorage Token",
            status: localToken ? "pass" : "fail",
            message: localToken ? "Token in localStorage" : "No token in localStorage",
            details: localToken ? `Matches auth: ${localToken === authToken}` : null,
        }

        // Test 3: Test API client configuration
        results.apiConfig = {
            name: "API Client Config",
            status: apiClient.defaults.baseURL ? "pass" : "warn",
            message: apiClient.defaults.baseURL ? "Base URL configured" : "No base URL set",
            details: {
                baseURL: apiClient.defaults.baseURL,
                timeout: apiClient.defaults.timeout,
                hasAuthHeader: !!apiClient.defaults.headers?.Authorization,
            },
        }

        // Test 4: Simple connectivity test
        try {
            const response = await fetch("/api/v1/users/list/data?start=0&length=1&draw=1", {
                method: "GET",
                headers: {
                    Authorization: `Bearer ${authToken}`,
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            })

            results.connectivity = {
                name: "API Connectivity",
                status: response.ok ? "pass" : "fail",
                message: response.ok ? `Connected (${response.status})` : `Failed (${response.status})`,
                details: {
                    status: response.status,
                    statusText: response.statusText,
                    headers: Object.fromEntries(response.headers.entries()),
                },
            }

            if (response.ok) {
                const data = await response.text()
                results.connectivity.details.responsePreview = data.substring(0, 200)
            }
        } catch (error: any) {
            results.connectivity = {
                name: "API Connectivity",
                status: "fail",
                message: `Connection failed: ${error.message}`,
                details: { error: error.message },
            }
        }

        // Test 5: Test with apiClient
        try {
            const response = await apiClient.get("/api/v1/users/list/data", {
                params: { start: 0, length: 1, draw: 1 },
                timeout: 10000,
            })

            results.apiClient = {
                name: "API Client Test",
                status: "pass",
                message: `Success (${response.status})`,
                details: {
                    status: response.status,
                    dataType: typeof response.data,
                    hasData: !!response.data?.data,
                    recordCount: response.data?.data?.length || 0,
                },
            }
        } catch (error: any) {
            results.apiClient = {
                name: "API Client Test",
                status: "fail",
                message: `Failed: ${error.message}`,
                details: {
                    error: error.message,
                    status: error.response?.status,
                    responseData: error.response?.data,
                },
            }
        }

        setTests(results)
        setRunning(false)
    }

    useEffect(() => {
        runTests()
    }, [authToken])

    const getStatusIcon = (status: string) => {
        switch (status) {
            case "pass":
                return <CheckCircle className="w-4 h-4 text-green-500" />
            case "fail":
                return <XCircle className="w-4 h-4 text-red-500" />
            case "warn":
                return <AlertCircle className="w-4 h-4 text-yellow-500" />
            default:
                return <AlertCircle className="w-4 h-4 text-gray-500" />
        }
    }

    const getStatusBadge = (status: string) => {
        const variants: any = {
            pass: "default",
            fail: "destructive",
            warn: "secondary",
        }
        return (
            <Badge variant={variants[status] || "outline"} className="ml-2">
                {status.toUpperCase()}
            </Badge>
        )
    }

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle>API Connection Diagnostics</CardTitle>
                    <Button onClick={runTests} disabled={running} variant="outline" size="sm">
                        <RefreshCw className={`w-4 h-4 mr-2 ${running ? "animate-spin" : ""}`} />
                        Run Tests
                    </Button>
                </div>
            </CardHeader>
            <CardContent className="space-y-4">
                {Object.entries(tests).map(([key, test]: [string, any]) => (
                    <div key={key} className="border rounded-lg p-4">
                        <div className="flex items-center justify-between mb-2">
                            <div className="flex items-center gap-2">
                                {getStatusIcon(test.status)}
                                <h4 className="font-medium">{test.name}</h4>
                                {getStatusBadge(test.status)}
                            </div>
                        </div>
                        <p className="text-sm text-gray-600 mb-2">{test.message}</p>
                        {test.details && (
                            <details className="text-xs">
                                <summary className="cursor-pointer text-gray-500 hover:text-gray-700">View Details</summary>
                                <pre className="mt-2 bg-gray-50 p-2 rounded overflow-auto">{JSON.stringify(test.details, null, 2)}</pre>
                            </details>
                        )}
                    </div>
                ))}

                {/* Quick Fix Suggestions */}
                <Alert>
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>
                        <strong>Quick Fixes:</strong>
                        <ul className="mt-2 space-y-1 text-sm">
                            {!authToken && <li>• Check if user is properly authenticated</li>}
                            {tests.connectivity?.status === "fail" && <li>• Verify API endpoint exists and is accessible</li>}
                            {tests.apiClient?.status === "fail" && <li>• Check CORS configuration on your backend</li>}
                            {!tests.apiConfig?.details?.baseURL && <li>• Configure base URL in apiClient</li>}
                        </ul>
                    </AlertDescription>
                </Alert>
            </CardContent>
        </Card>
    )
}
