'use client'
import { ArrowRight } from 'lucide-react'
import { Link } from '@inertiajs/react';
import React from 'react'

const Title = ({ title, description, visibleButton = true, href = '' }) => {

    return (
        <div className='flex flex-col items-center'>
            <h2 className='text-4xl sm:text-5xl font-bold text-center mb-6 text-foreground'>{title}</h2>
            <Link href={href} className='text-center text-muted-foreground text-lg max-w-3xl mx-auto mt-2 leading-relaxed'>
                <p className='max-w-lg text-center'>{description}</p>
                {visibleButton && <button className='text-green-500 flex items-center gap-1'>View more <ArrowRight size={14} /></button>}
            </Link>
        </div>
    )
}

export default Title
