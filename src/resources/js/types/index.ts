import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    [key: string]: unknown;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

export interface User {
    id: string; // uuid
    name?: string;
    first_name?: string;
    last_name?: string;
    middle_name?: string | null;
    suffix?: string | null;
    gender?: string;
    birthdate: string;
    phone_number?: string | null;
    email: string;
    avatar?: string;
    email_verified_at?: string | null;
    staff?: Staff | null;
    created_at: string;
    updated_at: string;
    parent_guardian?: ParentGuardian | null;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Staff {
    id: string;
    staff_number: string;
    is_admin?: boolean;
    latest_position?: Position | null;
    user: User | null;
    positions?: Position[] | null;
    section_advisers?: GradeSection[] | null;
    // resolved_security?: SecurityEvent[] | null;
}

export interface Position {
    id: number;
    position_name: string;
    department?: string | null;
    pivot?: PositionPivot;
    positions?: Position[] | null;
}

export interface PositionPivot {
    assigned_at: string | null;
}

export interface StudentWithParentGuardianPivot {
  pivot: ParentGuardianPivot;
}

export interface ParentGuardianPivot {
    relationship: string | null;
}

export interface StudentParentGuardianPivot {
    relationship: string | null;
}

export interface ParentGuardian {
    id: string;
    user: User;
    parent_code: string | null;
    occupation: string | null;
    pivot: ParentGuardianPivot;
    students: Student[];
}

export interface Student {
    id: string;
    student_number: string;
    first_name: string;
    last_name: string;
    middle_name?: string | null;
    suffix?: string | null;
    gender: string;
    birthdate: string;
    email?: string | null;
    phone_number?: string | null;
    status?: string;
    latest_section?: GradeSection | null;
    isAlumni?: boolean;
    grade_sections: GradeSection[] | null;
    latest_enrollment: Enrollment | null;
    parent_guardians: ParentGuardian[];
    pivot?: StudentParentGuardianPivot;
    reports: StudentReport[];
    disciplinary_actions: DisciplinaryAction[];
    created_at: string;
    updated_at: string;
}

export interface GradeSection {
    id: number;
    school_year: SchoolYear;
    students: Student[];
    adviser?: Staff | null;
    grade_level: string;
    section: string;
}

export interface SchoolYear {
    id: number;
    school_year: string;
    is_active: boolean;
}

export interface Enrollment {
    grade_section: GradeSection | null;
    school_year?: SchoolYear | null;
}

export interface DisciplinaryAction {
    id: string;
    report?: Report | null
    discipline_action: string;
    notes: string | null;
    staff: Staff | null;
}

/* export interface Report {
    id: string;
    incident_title: string;
    description: string | null;
    location: string | null;
    created_at: string;
    user: ReportUser | null;
    category: ReportCategory | null;
    current_status: ReportStatus | null;
} */

export interface Report {
    id: string;
    report_code?: string | null;
    incident_title: string;
    description: string | null;
    location: string | null;
    incident_date?: string | null;
    incident_time?: string | null;
    created_at: string;
    user: ReportUser | null;
    category: Category | null;
    current_status: ReportStatus | null;
    severity?: string | null;
    escalation_level?: number | null;
    current_level?: number | null;
    current_assignee?: CurrentAssignee | null;
    can_act?: boolean;
    read_only?: boolean;
}

export interface Category {
    id: number;
    category_name: string;
}

export interface CurrentAssignee {
    staff_number?: string | null;
    user?: ReportUser | null;
}

export interface ReportStatus {
    status_name: string;
}

export interface ReportUser {
    first_name: string;
    last_name: string;
}

export interface ReportCategory {
    id: number;
    category_name: string;
}

export interface ReportStatus {
    status_name: string;
}

export interface ReportPivot {
    involvement_type: string | null;
    notes: string | null;
}

export interface StudentReport {
    id: number;
    incident_title: string | null;
    category: ReportCategory | null;
    pivot: ReportPivot;
}

export interface AiAnalysis {
    id: string;
    periodicity: string;
    period_label: string;
    output_html: string;
    created_at: string;
    updated_at?: string;
}

export interface DashboardMetric {
    value: number;
    change?: number | null;
}

export interface DashboardCategoryStat {
    category_name: string;
    total: number;
    percentage: number;
}

export interface DashboardStatusStat {
    status_name: string;
    total: number;
    percentage: number;
}

export interface DashboardMetrics {
    weekly_reported: number;
    weekly_resolved: number;
    weekly_change: number | null;
    monthly_reported: number;
    monthly_change: number | null;
    academic_year_reported: number;
    ongoing_reports: number;
    resolved_reports: number;
    resolution_rate: number;
    open_rate: number;
    academic_year_label: string;
}

export interface DashboardProps {
    dashboardMetrics: DashboardMetrics;
    topCategories: DashboardCategoryStat[];
    statusDistribution: DashboardStatusStat[];
    latestAnalysis: AiAnalysis | null;
    analyses: Paginated<AiAnalysis>;
    analysisFilter: string;
}

