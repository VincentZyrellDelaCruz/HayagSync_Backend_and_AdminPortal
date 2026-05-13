@extends('layouts.app')

@section('title', 'User Panel (Staff) - HayagSync')

@section('content')

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-5">
                <div class="p-6 text-gray-900">
                    <h2 class="font-bold text-2xl mb-5">OSD Staff and Administrator List</h2>
                    <table id="staff-table" class="display">
                        <thead>
                            <tr>
                                <th>Staff Number</th>
                                <th>Last Name</th>
                                <th>First Name</th>
                                <th>M.I</th>
                                <th>Suffix</th>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="font-bold text-2xl mb-5">Parent/Guardian List</h2>
                    <table id="parent-table" class="display">
                        <thead>
                            <tr>
                                <th>Staff Number</th>
                                <th>Last Name</th>
                                <th>First Name</th>
                                <th>M.I</th>
                                <th>Suffix</th>
                                <th>Occupation</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#staff-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('user.staff.data') }}",
                columns: [
                    { data: 'staff_number', name: 'staff_number' },
                    { data: 'first_name', name: 'first_name' },
                    { data: 'last_name', name: 'last_name' },
                    { data: 'middle_name', name: 'middle_name' },
                    { data: 'suffix', name: 'suffix' },
                    { data: 'position', name: 'position' },
                    { data: 'department', name: 'department' },
                    { data: 'action', name: 'action', orderable: false, searchable: false},
                ]
            });
        });

        $(document).ready(function() {
            $('#parent-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('user.parent.data') }}",
                columns: [
                    { data: 'parent_code', name: 'parent_code' },
                    { data: 'first_name', name: 'first_name' },
                    { data: 'last_name', name: 'last_name' },
                    { data: 'middle_name', name: 'middle_name' },
                    { data: 'suffix', name: 'suffix' },
                    { data: 'occupation', name: 'occupation' },
                    { data: 'action', name: 'action', orderable: false, searchable: false},
                ]
            });
        });
    </script>
@endsection
