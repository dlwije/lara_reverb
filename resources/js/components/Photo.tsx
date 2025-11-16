"use client"

import type React from "react"

import { useState, useRef } from "react"
import { Label } from "@/components/ui/label"
import { cn } from "@/lib/utils"

interface PhotoProps {
    id?: string
    multiple?: boolean
    accept?: string
    label?: string
    error?: string
    onChange?: (files: FileList | File | null) => void
    className?: string
}

export default function Photo({
                                  id = Math.random().toString().replace("0.", ""),
                                  multiple = false,
                                  accept = "image/*",
                                  label,
                                  error,
                                  onChange,
                                  className = "",
                              }: PhotoProps) {
    const [selectedText, setSelectedText] = useState("")
    const fileInputRef = useRef<HTMLInputElement>(null)

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const files = e.target.files
        if (!files) {
            setSelectedText("")
            onChange?.(null)
            return
        }

        let text = ""
        if (files.length === 1) {
            text = "1 file selected"
        } else if (files.length > 1) {
            text = `${files.length} files selected`
        }

        setSelectedText(text)
        onChange?.(multiple ? files : files[0])
    }

    const handleLabelClick = () => {
        fileInputRef.current?.click()
    }

    return (
        <div className={className}>
            {label && (
                <Label htmlFor={id} className="mb-1 block">
                    {label}
                </Label>
            )}

            <input
                ref={fileInputRef}
                id={id}
                multiple={multiple}
                type="file"
                name="photo"
                accept={accept}
                className="invisible absolute"
                onChange={handleFileChange}
            />

            <label
                htmlFor={id}
                onClick={handleLabelClick}
                className={cn(
                    "cursor-pointer inline-block py-2 px-4 border text-left w-full focus:border-blue-300 focus:ring-3 focus:ring-blue-200/50 rounded-md shadow-sm bg-white dark:bg-gray-900 transition-colors",
                    {
                        "border-red-500": error,
                        "text-gray-700 dark:text-gray-300": selectedText,
                        "text-gray-400 dark:text-gray-600": !selectedText,
                        "border-gray-300 dark:border-gray-700": !error,
                    },
                )}
            >
                {selectedText || "Select"}
            </label>

            {error && <div className="mt-1 text-sm text-red-600">{error}</div>}
        </div>
    )
}
