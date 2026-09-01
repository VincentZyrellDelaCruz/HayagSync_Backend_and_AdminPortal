import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { GraduationCap, LayoutGrid, Megaphone, Shield, Users, Wrench } from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth } = usePage().props as any;

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            url: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'Incident Reports',
            url: route('web.reports.index'),
            icon: Megaphone,
        },
        {
            title: 'Students',
            url: route('web.students.index'),
            icon: GraduationCap,
        },
        {
            title: 'Parent/Staff Directory',
            url: route('web.users.index'),
            icon: Users,
        },
        ...(auth?.user?.staff?.is_admin
        ? [
            {
                title: 'Security Center',
                url: route('web.admin.security.index'),
                icon: Shield,
            },
            {
                title: 'Admin Settings',
                url: route('dashboard'),
                icon: Wrench,
            },
            ]
        : []),
    ];

    const footerNavItems: NavItem[] = [
        /* {
            title: 'Repository',
            url: 'https://github.com/laravel/react-starter-kit',
            icon: Folder,
        },
        {
            title: 'Documentation',
            url: 'https://laravel.com/docs/starter-kits',
            icon: BookOpen,
        }, */
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
