<div class="card account-nav border-0 shadow mb-4 mb-lg-0">
    <div class="card-body p-0">
        <ul class="list-group list-group-flush ">
            <li class="list-group-item d-flex justify-content-between p-3 fs-5">
                <a href="{{ route('admin.users') }}">Usuarios</a>
            </li>
            <li class="list-group-item d-flex justify-content-between p-3 fs-5">
                <a href="{{ route('admin.categories') }}">Categorias</a>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center p-3 fs-5">
                <a href="{{ route('admin.jobs') }}">Empleos</a>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center p-3 fs-5">
                <a href="{{ route('admin.jobApplications') }}">Solicitudes de Empleo</a>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center p-3 fs-5">
                <a href="{{ route('account.logout') }}">Cerrar Sesion</a>
            </li>
        </ul>
    </div>
</div>
