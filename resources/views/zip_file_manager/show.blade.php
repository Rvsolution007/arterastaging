@extends("layouts.app")

@section('extra_css')
    <style>
        .zip-tree ul {
            list-style-type: none;
            padding-left: 20px;
            margin: 0;
            position: relative;
        }

        .zip-tree ul ul::before {
            content: "";
            position: absolute;
            top: 0;
            left: 8px;
            border-left: 1px solid #ccc;
            height: 100%;
        }

        .zip-tree li {
            margin: 0;
            padding: 5px 0;
            line-height: normal;
            position: relative;
            font-size: 14px;
            color: #333;
        }

        .zip-tree li::before {
            content: "";
            position: absolute;
            top: 15px;
            left: -12px;
            width: 10px;
            height: 1px;
            border-top: 1px solid #ccc;
        }

        .zip-tree li:last-child::before {
            background: white;
            /* Hide the line part that goes below the last item */
            height: auto;
            top: 15px;
            bottom: 0;
        }

        .zip-tree .folder-icon {
            color: #ffca28;
            margin-right: 5px;
        }

        .zip-tree .file-icon {
            color: #78909c;
            margin-right: 5px;
        }

        .zip-tree .node-name {
            cursor: default;
        }

        .zip-tree li:hover {
            background-color: #f5f5f5;
        }
    </style>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">ZIP Manager: {{ $zip->file_name }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('zip-file-manager.index') }}" class="btn btn-default btn-sm text-dark">Back to
                            List</a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Breadcrumbs -->
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('zip-file-manager.show', $zip->id) }}"><i
                                        class="fas fa-home"></i> Root</a></li>
                            @foreach($breadcrumbs as $breadcrumb)
                                <li class="breadcrumb-item"><a
                                        href="{{ route('zip-file-manager.show', ['zip_file_manager' => $zip->id, 'path' => $breadcrumb['path']]) }}">{{ $breadcrumb['name'] }}</a>
                                </li>
                            @endforeach
                        </ol>
                    </nav>

                    <div class="zip-explorer">
                        <div class="row">
                            <!-- Show Back button if not in root -->
                            @if($currentPath !== '')
                                @php
                                    $prevPath = '';
                                    if (strpos($currentPath, '/') !== false) {
                                        $prevPath = substr($currentPath, 0, strrpos($currentPath, '/'));
                                    }
                                @endphp
                                <div class="col-md-2 col-sm-3 col-4 mb-3">
                                    <a href="{{ route('zip-file-manager.show', ['zip_file_manager' => $zip->id, 'path' => $prevPath]) }}"
                                        class="text-center d-block p-2 text-dark border">
                                        <i class="fas fa-level-up-alt fa-3x text-secondary d-block mb-1"></i>
                                        <span>..</span>
                                    </a>
                                </div>
                            @endif

                            @foreach($folders as $folder)
                                <div class="col-md-2 col-sm-3 col-4 mb-3">
                                    <a href="{{ route('zip-file-manager.show', ['zip_file_manager' => $zip->id, 'path' => ($currentPath !== '' ? $currentPath . '/' : '') . $folder]) }}"
                                        class="text-center d-block p-2 text-dark border explorer-item">
                                        <i class="fas fa-folder fa-3x text-warning d-block mb-1"></i>
                                        <span class="text-truncate d-block">{{ $folder }}</span>
                                    </a>
                                </div>
                            @endforeach

                            @foreach($files as $file)
                                <div class="col-md-2 col-sm-3 col-4 mb-3 text-center border p-2">
                                    @php
                                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                        $icon = 'fa-file';
                                        $color = 'text-secondary';
                                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'svg'])) {
                                            $icon = 'fa-file-image';
                                            $color = 'text-info';
                                        } elseif ($ext == 'zip') {
                                            $icon = 'fa-file-archive';
                                            $color = 'text-danger';
                                        } elseif ($ext == 'php') {
                                            $icon = 'fa-file-code';
                                            $color = 'text-primary';
                                        }
                                    @endphp
                                    <i class="fas {{ $icon }} fa-3x {{ $color }} d-block mb-1"></i>
                                    <span class="text-truncate d-block">{{ $file }}</span>
                                </div>
                            @endforeach
                        </div>

                        @if(empty($folders) && empty($files))
                            <div class="text-center py-5">
                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                <p>This folder is empty.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection