<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable - Academia Pro</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "inverse-primary": "#b6c4ff",
                        "outline": "#757682",
                        "surface-container-lowest": "#ffffff",
                        "on-secondary-container": "#fefcff",
                        "primary-container": "#1e3a8a",
                        "secondary": "#0051d5",
                        "on-primary-container": "#90a8ff",
                        "on-error": "#ffffff",
                        "surface-dim": "#d8dadc",
                        "on-tertiary-fixed": "#0b1c30",
                        "surface-tint": "#4059aa",
                        "secondary-fixed": "#dbe1ff",
                        "inverse-surface": "#2d3133",
                        "error": "#ba1a1a",
                        "inverse-on-surface": "#eff1f3",
                        "on-secondary-fixed-variant": "#003ea8",
                        "on-primary-fixed": "#00164e",
                        "surface-container-high": "#e6e8ea",
                        "primary": "#00236f",
                        "background": "#f7f9fb",
                        "on-background": "#191c1e",
                        "surface-variant": "#e0e3e5",
                        "tertiary-fixed-dim": "#b7c8e1",
                        "tertiary-container": "#314156",
                        "on-surface": "#191c1e",
                        "on-surface-variant": "#444651",
                        "on-primary": "#ffffff",
                        "primary-fixed-dim": "#b6c4ff",
                        "surface": "#f7f9fb",
                        "surface-container-low": "#f2f4f6",
                        "outline-variant": "#c5c5d3",
                        "surface-container": "#eceef0",
                        "on-secondary-fixed": "#00174b",
                        "on-secondary": "#ffffff",
                        "on-error-container": "#93000a",
                        "surface-container-highest": "#e0e3e5",
                        "secondary-container": "#316bf3",
                        "tertiary": "#1b2b3f",
                        "secondary-fixed-dim": "#b4c5ff",
                        "primary-fixed": "#dce1ff",
                        "tertiary-fixed": "#d3e4fe",
                        "on-tertiary-container": "#9dadc6",
                        "on-primary-fixed-variant": "#264191",
                        "surface-bright": "#f7f9fb",
                        "on-tertiary": "#ffffff",
                        "error-container": "#ffdad6",
                        "on-tertiary-fixed-variant": "#38485d"
                    },
                    borderRadius: {
                        DEFAULT: "0.125rem",
                        lg: "0.25rem",
                        xl: "0.5rem",
                        full: "0.75rem"
                    },
                    spacing: {
                        xs: "4px",
                        gutter: "24px",
                        md: "16px",
                        sm: "8px",
                        lg: "24px",
                        "sidebar-width": "260px",
                        "container-max": "1440px",
                        xl: "32px",
                        base: "4px"
                    },
                    fontFamily: {
                        "label-sm": ["Inter"],
                        "headline-md": ["Inter"],
                        "label-md": ["Inter"],
                        "headline-xl": ["Inter"],
                        "headline-xl-mobile": ["Inter"],
                        "body-lg": ["Inter"],
                        "body-md": ["Inter"],
                        "headline-lg": ["Inter"],
                        "body-sm": ["Inter"]
                    },
                    fontSize: {
                        "label-sm": ["12px", {"lineHeight": "16px", "fontWeight": "500"}],
                        "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                        "label-md": ["14px", {"lineHeight": "20px", "letterSpacing": "0.05em", "fontWeight": "600"}],
                        "headline-xl": ["32px", {"lineHeight": "40px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                        "headline-xl-mobile": ["24px", {"lineHeight": "32px", "fontWeight": "700"}],
                        "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                        "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                        "headline-lg": ["24px", {"lineHeight": "32px", "letterSpacing": "-0.01em", "fontWeight": "600"}],
                        "body-sm": ["12px", {"lineHeight": "16px", "fontWeight": "400"}]
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .timetable-grid {
            display: grid;
            grid-template-columns: 80px repeat(5, 1fr);
            grid-template-rows: auto repeat(10, 80px);
        }
        .time-line {
            position: absolute;
            left: 0;
            right: 0;
            border-top: 2px solid #ef4444;
            pointer-events: none;
            z-index: 20;
        }
        .time-line::before {
            content: '';
            position: absolute;
            left: -4px;
            top: -4px;
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
    </style>
</head>
<body class="bg-background text-on-surface font-body-md">

    <!-- SideNavBar -->
    <aside class="w-sidebar-width h-full fixed left-0 top-0 bg-primary dark:bg-tertiary-container border-r border-outline-variant dark:border-outline flex flex-col h-screen py-lg z-50">
        <div class="px-lg mb-xl">
            <h1 class="text-headline-md font-headline-md font-bold text-on-primary">Academia Pro</h1>
            <p class="text-label-md text-on-primary/60">Instructor Portal</p>
        </div>
        <nav class="flex-1 px-sm space-y-xs">
            <a class="flex items-center gap-md px-md py-sm text-on-primary/70 hover:text-on-primary hover:bg-primary-container/50 transition-colors" href="#">
                <span class="material-symbols-outlined">dashboard</span>
                <span class="font-label-md text-label-md">Dashboard</span>
            </a>
            <a class="flex items-center gap-md px-md py-sm bg-secondary-container text-on-secondary-container border-l-4 border-secondary-fixed rounded-r-full" href="#">
                <span class="material-symbols-outlined">calendar_month</span>
                <span class="font-label-md text-label-md">Timetable</span>
            </a>
            <a class="flex items-center gap-md px-md py-sm text-on-primary/70 hover:text-on-primary hover:bg-primary-container/50 transition-colors" href="#">
                <span class="material-symbols-outlined">task_alt</span>
                <span class="font-label-md text-label-md">My Tasks</span>
            </a>
            <a class="flex items-center gap-md px-md py-sm text-on-primary/70 hover:text-on-primary hover:bg-primary-container/50 transition-colors" href="#">
                <span class="material-symbols-outlined">swap_horiz</span>
                <span class="font-label-md text-label-md">Replacement Requests</span>
            </a>
            <a class="flex items-center gap-md px-md py-sm text-on-primary/70 hover:text-on-primary hover:bg-primary-container/50 transition-colors" href="#">
                <span class="material-symbols-outlined">group</span>
                <span class="font-label-md text-label-md">Student Records</span>
            </a>
            <a class="flex items-center gap-md px-md py-sm text-on-primary/70 hover:text-on-primary hover:bg-primary-container/50 transition-colors mt-auto" href="#">
                <span class="material-symbols-outlined">settings</span>
                <span class="font-label-md text-label-md">Settings</span>
            </a>
        </nav>
        <div class="px-lg mt-lg">
            <button class="w-full py-md bg-secondary text-on-secondary font-label-md rounded-xl hover:opacity-90 transition-opacity">
                New Request
            </button>
        </div>
    </aside>

    <!-- TopNavBar -->
    <header class="flex justify-between items-center h-16 px-gutter ml-sidebar-width bg-surface dark:bg-surface-dim border-b border-outline-variant dark:border-outline shadow-sm sticky top-0 z-40">
        <div class="flex items-center gap-md flex-1">
            <div class="relative w-96">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">search</span>
                <input class="w-full pl-10 pr-4 py-2 bg-surface-container-low border border-outline-variant rounded-full text-body-md focus:outline-none focus:ring-2 focus:ring-secondary/20" placeholder="Search sessions, rooms, students..." type="text">
            </div>
        </div>
        <div class="flex items-center gap-lg">
            <div class="flex gap-sm">
                <button class="hover:bg-surface-container-high dark:hover:bg-surface-container-highest rounded-full p-2 transition-transform active:scale-90">
                    <span class="material-symbols-outlined text-on-surface-variant">notifications</span>
                </button>
                <button class="hover:bg-surface-container-high dark:hover:bg-surface-container-highest rounded-full p-2 transition-transform active:scale-90">
                    <span class="material-symbols-outlined text-on-surface-variant">help_outline</span>
                </button>
            </div>
            <div class="flex items-center gap-sm border-l border-outline-variant pl-lg">
                <div class="text-right">
                    <p class="text-label-md text-on-surface font-bold">Dr. John Silva</p>
                    <p class="text-body-sm text-on-surface-variant">Senior Lecturer</p>
                </div>
                <img class="w-10 h-10 rounded-full border-2 border-primary-container" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDr5QzPwJG4hKeg1lZ3ewUqHvbg-LNPm8nXM5an5xkjure0YJ0iFNkwN49zAUTRPoz0F2KOck4sxsli41SJVMOpTxJCMP_JJVwPrFahUmV70P-1N0G63xw0niZ1s498YJS5UPIOWKVI1yBh_l6wpGUQqyc4B1xY0fZzfe1sSAe3KWKzbTOe424BecR60hAfISnO9lqZCtb63y5ffH37Iq9hFut-FZojgaQKKrOKFc0htwoc5EaqQ9tOfA" alt="Profile">
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="ml-sidebar-width p-gutter space-y-lg bg-background min-h-[calc(100vh-64px)]">
        
        <!-- Header & View Controls -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-md">
            <div>
                <h2 class="text-headline-xl font-headline-xl text-primary mb-1">Timetable</h2>
                <p class="text-body-md text-on-surface-variant">Managing academic schedules for the Semester 1, 2024</p>
            </div>
            <div class="flex items-center gap-sm bg-surface-container-high p-1 rounded-lg border border-outline-variant">
                <button class="px-md py-1.5 text-label-md text-on-surface-variant hover:text-on-surface">Day</button>
                <button class="px-md py-1.5 text-label-md bg-white shadow-sm rounded-md text-primary font-bold">Week</button>
                <button class="px-md py-1.5 text-label-md text-on-surface-variant hover:text-on-surface">Month</button>
            </div>
            <div class="flex items-center gap-md">
                <button class="flex items-center gap-sm px-lg py-md border border-outline-variant rounded-xl bg-white text-on-surface-variant font-label-md hover:bg-surface-container-low transition-colors">
                    <span class="material-symbols-outlined text-[20px]">print</span>
                    Print
                </button>
                <button class="flex items-center gap-sm px-lg py-md bg-primary text-on-primary rounded-xl font-label-md hover:opacity-95 transition-opacity">
                    <span class="material-symbols-outlined text-[20px]">ios_share</span>
                    Export CSV
                </button>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-md p-lg bg-white border border-outline-variant rounded-xl shadow-sm">
            <div class="space-y-xs">
                <label class="text-label-sm text-on-surface-variant">Course Filter</label>
                <select class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-md py-sm text-body-md focus:ring-secondary focus:border-secondary">
                    <option>All Courses</option>
                    <option>IS1205 - Database Systems</option>
                    <option>IS2202 - Algorithms</option>
                    <option>EN1202 - Communication</option>
                </select>
            </div>
            <div class="space-y-xs">
                <label class="text-label-sm text-on-surface-variant">Venue Filter</label>
                <select class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-md py-sm text-body-md focus:ring-secondary focus:border-secondary">
                    <option>All Venues</option>
                    <option>Hall A (Main Block)</option>
                    <option>Lab 4 (CS Wing)</option>
                    <option>Seminar Room 2</option>
                </select>
            </div>
            <div class="space-y-xs">
                <label class="text-label-sm text-on-surface-variant">Session Type</label>
                <select class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-md py-sm text-body-md focus:ring-secondary focus:border-secondary">
                    <option>All Types</option>
                    <option>Lecture</option>
                    <option>Practical</option>
                    <option>Meeting</option>
                </select>
            </div>
            <div class="flex items-end pb-1 gap-md">
                <button class="text-label-md text-secondary hover:underline px-md">Clear All Filters</button>
            </div>
        </div>

        <!-- Calendar Grid -->
        <div class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden flex flex-col">
            
            <!-- Grid Header: Days -->
            <div class="timetable-grid border-b border-outline-variant bg-surface-container-low">
                <div class="p-md text-center border-r border-outline-variant">
                    <span class="material-symbols-outlined text-on-surface-variant">schedule</span>
                </div>
                <div class="p-md text-center border-r border-outline-variant">
                    <p class="text-label-sm text-on-surface-variant">MON</p>
                    <p class="text-headline-md text-primary">12</p>
                </div>
                <div class="p-md text-center border-r border-outline-variant bg-secondary-fixed/30">
                    <p class="text-label-sm text-secondary font-bold">TUE</p>
                    <p class="text-headline-md text-secondary">13</p>
                </div>
                <div class="p-md text-center border-r border-outline-variant">
                    <p class="text-label-sm text-on-surface-variant">WED</p>
                    <p class="text-headline-md text-primary">14</p>
                </div>
                <div class="p-md text-center border-r border-outline-variant">
                    <p class="text-label-sm text-on-surface-variant">THU</p>
                    <p class="text-headline-md text-primary">15</p>
                </div>
                <div class="p-md text-center">
                    <p class="text-label-sm text-on-surface-variant">FRI</p>
                    <p class="text-headline-md text-primary">16</p>
                </div>
            </div>

            <!-- Scrollable Grid Body -->
            <div class="relative overflow-y-auto max-h-[800px] custom-scrollbar">
                <div class="time-line top-[260px] left-[80px]" style="width: calc(100% - 80px)"></div>
                
                <div class="timetable-grid relative">
                    <!-- Time Column -->
                    <div class="flex flex-col border-r border-outline-variant">
                        <div class="h-[80px] flex items-start justify-center pt-2 text-label-sm text-on-surface-variant">08:00 AM</div>
                        <div class="h-[80px] flex items-start justify-center pt-2 text-label-sm text-on-surface-variant">09:00 AM</div>
                        <div class="h-[80px] flex items-start justify-center pt-2 text-label-sm text-on-surface-variant">10:00 AM</div>
                        <div class="h-[80px] flex items-start justify-center pt-2 text-label-sm text-on-surface-variant">11:00 AM</div>
                        <div class="h-[80px] flex items-start justify-center pt-2 text-label-sm text-on-surface-variant">12:00 PM</div>
                        <div class="h-[80px] flex items-start justify-center pt-2 text-label-sm text-on-surface-variant">01:00 PM</div>
                        <div class="h-[80px] flex items-start justify-center pt-2 text-label-sm text-on-surface-variant">02:00 PM</div>
                        <div class="h-[80px] flex items-start justify-center pt-2 text-label-sm text-on-surface-variant">03:00 PM</div>
                        <div class="h-[80px] flex items-start justify-center pt-2 text-label-sm text-on-surface-variant">04:00 PM</div>
                        <div class="h-[80px] flex items-start justify-center pt-2 text-label-sm text-on-surface-variant">05:00 PM</div>
                    </div>

                    <!-- Column Grid Lines Background -->
                    <div class="col-start-2 col-span-5 row-start-1 row-span-10 grid grid-cols-5 grid-rows-10 pointer-events-none">
                        <div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-b border-outline-variant/30"></div>
                        <div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-b border-outline-variant/30"></div>
                        <div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-b border-outline-variant/30"></div>
                        <div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-b border-outline-variant/30"></div>
                        <div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-b border-outline-variant/30"></div>
                        <div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-b border-outline-variant/30"></div>
                        <div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-b border-outline-variant/30"></div>
                        <div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-b border-outline-variant/30"></div>
                        <div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-r border-b border-outline-variant/30"></div><div class="border-b border-outline-variant/30"></div>
                        <div class="border-r border-outline-variant/30"></div><div class="border-r border-outline-variant/30"></div><div class="border-r border-outline-variant/30"></div><div class="border-r border-outline-variant/30"></div><div></div>
                    </div>

                    <!-- Events Overlay -->
                    <div class="col-start-2 row-start-2 row-span-2 p-1">
                        <div class="h-full bg-secondary-container/10 border-l-4 border-secondary-container rounded-lg p-md shadow-sm hover:scale-[1.02] transition-transform cursor-pointer">
                            <div class="flex justify-between items-start mb-sm">
                                <span class="bg-secondary-container text-white text-[10px] px-2 py-0.5 rounded-full uppercase font-bold">Lecture</span>
                                <span class="material-symbols-outlined text-secondary text-[18px]">school</span>
                            </div>
                            <p class="text-label-md font-bold text-primary">IS1205</p>
                            <p class="text-body-sm text-on-surface-variant font-medium">Database Systems</p>
                            <p class="text-label-sm text-secondary-container flex items-center gap-xs mt-sm">
                                <span class="material-symbols-outlined text-[14px]">location_on</span> Hall A
                            </p>
                        </div>
                    </div>

                    <div class="col-start-3 row-start-3 row-span-2 p-1">
                        <div class="h-full bg-primary-container/10 border-l-4 border-primary-container rounded-lg p-md shadow-sm hover:scale-[1.02] transition-transform cursor-pointer">
                            <div class="flex justify-between items-start mb-sm">
                                <span class="bg-primary-container text-white text-[10px] px-2 py-0.5 rounded-full uppercase font-bold">Practical</span>
                                <span class="material-symbols-outlined text-primary-container text-[18px]">terminal</span>
                            </div>
                            <p class="text-label-md font-bold text-primary">IS2202</p>
                            <p class="text-body-sm text-on-surface-variant font-medium">Algorithms Lab</p>
                            <p class="text-label-sm text-primary-container flex items-center gap-xs mt-sm">
                                <span class="material-symbols-outlined text-[14px]">location_on</span> Lab 4
                            </p>
                        </div>
                    </div>

                    <div class="col-start-4 row-start-6 row-span-1 p-1">
                        <div class="h-full bg-tertiary-container/10 border-l-4 border-tertiary-container rounded-lg p-md shadow-sm hover:scale-[1.02] transition-transform cursor-pointer">
                            <div class="flex justify-between items-start mb-sm">
                                <span class="bg-tertiary-container text-white text-[10px] px-2 py-0.5 rounded-full uppercase font-bold">Meeting</span>
                                <span class="material-symbols-outlined text-tertiary-container text-[18px]">groups</span>
                            </div>
                            <p class="text-label-md font-bold text-primary">Faculty Sync</p>
                            <p class="text-label-sm text-on-surface-variant flex items-center gap-xs mt-sm">
                                <span class="material-symbols-outlined text-[14px]">location_on</span> Conf. Room 3
                            </p>
                        </div>
                    </div>

                    <div class="col-start-5 row-start-7 row-span-2 p-1">
                        <div class="h-full bg-secondary-container/10 border-l-4 border-secondary-container rounded-lg p-md shadow-sm hover:scale-[1.02] transition-transform cursor-pointer">
                            <div class="flex justify-between items-start mb-sm">
                                <span class="bg-secondary-container text-white text-[10px] px-2 py-0.5 rounded-full uppercase font-bold">Lecture</span>
                                <span class="material-symbols-outlined text-secondary text-[18px]">school</span>
                            </div>
                            <p class="text-label-md font-bold text-primary">EN1202</p>
                            <p class="text-body-sm text-on-surface-variant font-medium">Communication Skills</p>
                            <p class="text-label-sm text-secondary-container flex items-center gap-xs mt-sm">
                                <span class="material-symbols-outlined text-[14px]">location_on</span> Hall B
                            </p>
                        </div>
                    </div>

                    <div class="col-start-6 row-start-9 row-span-2 p-1">
                        <div class="h-[120px] bg-outline/10 border-l-4 border-outline rounded-lg p-md shadow-sm hover:scale-[1.02] transition-transform cursor-pointer">
                            <div class="flex justify-between items-start mb-sm">
                                <span class="bg-outline text-white text-[10px] px-2 py-0.5 rounded-full uppercase font-bold">Research</span>
                                <span class="material-symbols-outlined text-outline text-[18px]">biotech</span>
                            </div>
                            <p class="text-label-md font-bold text-primary">PhD Supervision</p>
                            <p class="text-label-sm text-on-surface-variant flex items-center gap-xs mt-sm">
                                <span class="material-symbols-outlined text-[14px]">location_on</span> Office 302
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Footer Stats / Insights -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-lg">
            <div class="bg-white p-lg border border-outline-variant rounded-xl flex items-center gap-md">
                <div class="w-12 h-12 bg-secondary-fixed/50 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined text-secondary">history_edu</span>
                </div>
                <div>
                    <p class="text-body-sm text-on-surface-variant">Total Teaching Hours</p>
                    <p class="text-headline-md text-primary font-bold">18h / Week</p>
                </div>
            </div>
            <div class="bg-white p-lg border border-outline-variant rounded-xl flex items-center gap-md">
                <div class="w-12 h-12 bg-primary-fixed-dim/30 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary">meeting_room</span>
                </div>
                <div>
                    <p class="text-body-sm text-on-surface-variant">Rooms Utilized</p>
                    <p class="text-headline-md text-primary font-bold">4 Venues</p>
                </div>
            </div>
            <div class="bg-white p-lg border border-outline-variant rounded-xl flex items-center gap-md">
                <div class="w-12 h-12 bg-tertiary-fixed/50 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined text-tertiary">group_add</span>
                </div>
                <div>
                    <p class="text-body-sm text-on-surface-variant">Avg. Student Attendance</p>
                    <p class="text-headline-md text-primary font-bold">92%</p>
                </div>
            </div>
        </div>

    </main>

    <script>
        function updateTimeLine() {
            const now = new Date();
            const hours = now.getHours();
            const minutes = now.getMinutes();
            
            if (hours >= 8 && hours <= 18) {
                const totalMinutesSince8 = (hours - 8) * 60 + minutes;
                const pixelsPerMinute = 80 / 60;
                const topPos = totalMinutesSince8 * pixelsPerMinute;
                
                const timeLine = document.querySelector('.time-line');
                if (timeLine) {
                    timeLine.style.top = `${topPos}px`;
                    timeLine.style.display = 'block';
                }
            } else {
                const timeLine = document.querySelector('.time-line');
                if (timeLine) timeLine.style.display = 'none';
            }
        }

        updateTimeLine();
        setInterval(updateTimeLine, 60000);
    </script>
</body>
</html>