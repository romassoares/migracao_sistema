<div class="d-flex col-12" style="min-height:700px">
    <div class="container-fluid">
        <div class="row flex-nowrap">
            <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 bg-dark">
                <div class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-2 text-white min-vh-100">
                    <a href="" class="d-flex align-items-center pb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                        <span class="fs-5 d-none d-sm-inline">Menu</span>
                    </a>
                    <ul class="nav nav-pills flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start" id="menu">
                        <li class="nav-item d-flex">
                            <div class="form-group">
                                <label for="">Usuário</label>
                                <input type="text" class="form-control" value="<?php echo $_SESSION['login_usuario'] ?>">
                            </div>
                        </li>
                        <li class="nav-item d-flex">
                            <div class="form-group">
                                <label for="">Cliente</label>
                                <input type="text" class="form-control" value="<?php echo $_SESSION['nome_cliente'] ?>">
                            </div>
                        </li>
                        <li class="nav-item d-flex">
                            <a href="layout/index" class="nav-link px-0"> <span class="d-none d-sm-inline">Layout</span> </a>
                        </li>
                        <li class="nav-item d-flex">
                            <a href="concorrente/index" class="nav-link px-0"> <span class="d-none d-sm-inline">Concorrentes</span> </a>
                            <!-- <a onclick="routing('concorrentes/index.php')" class="nav-link px-0"> <span class="d-none d-sm-inline">Concorrentes</span> </a> -->
                        </li>


                    </ul>
                    <hr>
                    <div class="dropdown pb-4">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="d-none d-sm-inline mx-1"><?php echo $_SESSION['login_usuario'] ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                            <!-- <li><a class="dropdown-item" href="#">New project...</a></li>
                        <li><a class="dropdown-item" href="#">Settings</a></li> -->
                            <li><a class="dropdown-item" href="#">Mudar senha</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" onclick="logout()">Sign out</a></li>
                        </ul>
                    </div>
                </div>
            </div>