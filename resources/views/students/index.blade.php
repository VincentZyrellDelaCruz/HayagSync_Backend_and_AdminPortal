@extends('layouts.app')

@section('title', 'Students - HayagSync')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Students List</h1>

    {{-- Search & Filters --}}
    <form method="GET" action="{{ route('web.students.index') }}" class="mb-4 flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ $search }}"
               placeholder="Search by name or student number"
               class="flex-1 border rounded px-3 py-2">

        <select name="grade" class="border rounded px-3 py-2">
            <option value="">All Grades</option>
            @foreach($sections->pluck('grade_level')->unique() as $g)
                <option value="{{ $g }}" {{ $grade == $g ? 'selected' : '' }}>{{ $g }}</option>
            @endforeach
        </select>

        <select name="section" class="border rounded px-3 py-2">
            <option value="">All Sections</option>
            @foreach($sections as $s)
                <option value="{{ $s->section }}" {{ $section == $s->section ? 'selected' : '' }}>
                    {{ $s->section }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Filter</button>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Student No</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Name</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Grade</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Section</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($students as $student)
                    <tr class="hover:bg-slate-50 cursor-pointer"
                        onclick="window.location='{{ route('web.students.show', $student->id) }}'">
                        <td class="px-4 py-2 text-sm text-slate-800">{{ $student->student_number }}</td>
                        <td class="px-4 py-2 text-sm text-slate-800">
                            {{ $student->last_name }}, {{ $student->first_name }}
                        </td>
                        <td class="px-4 py-2 text-sm text-slate-600">{{ $student->grade_sections?->grade_level }}</td>
                        <td class="px-4 py-2 text-sm text-slate-600">{{ $student->grade_sections?->section }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-slate-500">No students found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        <div class="px-5 py-4 border-t border-slate-200 bg-white">
            {{ $students->appends(['search' => $search, 'grade' => $grade, 'section' => $section])->links() }}
        </div>
    </div>
</div>
@endsection
