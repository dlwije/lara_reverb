import React from 'react'
import Title from '@/components/e-commerce/public/title';
import { ourSpecsData } from '../../../../../public/e-commerce/assets/assets';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from "@/components/ui/button"
import { Clock, Shield, Zap } from 'lucide-react';

const OurSpecs = () => {

    return (
        <section className="py-20 px-4 sm:px-6 lg:px-8">
            <div className="max-w-7xl mx-auto">
                {/* Badge */}
                <div className="flex justify-center mb-6">
                    <div className="inline-flex items-center px-4 py-1.5 rounded-full border border-border bg-background text-sm font-medium text-foreground">
                        Why Choose Us?
                    </div>
                </div>

                {/* Heading */}
                <h2 className="text-4xl sm:text-5xl font-bold text-center mb-6 text-foreground">Experience the Difference</h2>

                {/* Subheading */}
                <p className="text-center text-muted-foreground text-lg max-w-3xl mx-auto mb-16 leading-relaxed">
                    {/*We provide lightning-fast service, 24/7 support, and top-tier security for all your needs. See how we stand*/}
                    {/*out from the competition.*/}
                    We offer top-tier service and convenience to ensure your shopping experience is smooth, secure and completely hassle-free.
                </p>

                {/* Feature Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                    {ourSpecsData.map((feature, index) => (
                        <Card key={index} className="bg-card border-border transition-all hover:border-muted-foreground/50">
                            <CardContent className="p-8 flex flex-col items-center text-center">
                                {/* Icon Container */}
                                <div className="mb-6 p-4 rounded-lg bg-muted">
                                    <feature.icon className="w-6 h-6 text-foreground" />
                                </div>

                                {/* Title */}
                                <h3 className="text-xl font-semibold mb-3 text-foreground">{feature.title}</h3>

                                {/* Description */}
                                <p className="text-muted-foreground leading-relaxed">{feature.description}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* CTA Button */}
                <div className="flex justify-center">
                    <Button size="lg" className="px-8">
                        Get Started Today
                    </Button>
                </div>
            </div>
        </section>
    )
}

export default OurSpecs
