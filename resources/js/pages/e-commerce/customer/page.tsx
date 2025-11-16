import { ChartAreaInteractive } from "./components/chart-area-interactive"
import { DataTable } from "./components/data-table"
import { SectionCards } from "./components/section-cards"

import data from "./data/data.json"
import pastPerformanceData from "./data/past-performance-data.json"
import keyPersonnelData from "./data/key-personnel-data.json"
import focusDocumentsData from "./data/focus-documents-data.json"
import PublicLayout from '@/pages/e-commerce/public/layout';
import CustomerSidebar from '@/components/e-commerce/customer/CustomerSidebar';

export default function Page() {
  return (
    <>
        <PublicLayout>
            <section className="bg-background dark min-h-screen">
                <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 flex">
                    {/* Sidebar on the left */}
                    <CustomerSidebar />

                    {/* Main content area on the right */}
                    <div className="flex-1">
                        {/* Page Title and Description */}
                        <div className="px-4 lg:px-6">
                            <div className="flex flex-col gap-2">
                                <h1 className="text-2xl font-bold tracking-tight">Dashboard</h1>
                                <p className="text-muted-foreground">Welcome to your dashboard</p>
                            </div>
                        </div>

                        <div className="@container/main px-4 lg:px-6 space-y-6">
                            <SectionCards />
                            <ChartAreaInteractive />
                        </div>
                        <div className="@container/main">
                            <DataTable
                                data={data}
                                pastPerformanceData={pastPerformanceData}
                                keyPersonnelData={keyPersonnelData}
                                focusDocumentsData={focusDocumentsData}
                            />
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    </>
  )
}
