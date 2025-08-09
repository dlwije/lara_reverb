import type { LucideIcon } from 'lucide-react'

export type TeamPlan = 'Enterprise' | 'Startup' | 'Free'

export interface Team {
    name: string
    logo: LucideIcon
    plan: TeamPlan
}

export interface NavChildItem {
    title: string
    url: string
}

export interface NavMainItem {
    title: string
    url: string
    icon: LucideIcon
    isActive?: boolean
    items?: NavChildItem[]
}

export interface ProjectItem {
    name: string
    url: string
    icon: LucideIcon
}

export interface NavItems {
    teams: Team[]
    navMain: NavMainItem[]
    projects: ProjectItem[]
}
