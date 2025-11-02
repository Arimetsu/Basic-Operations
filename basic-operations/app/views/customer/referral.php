<?php require_once ROOT_PATH . '/app/views/layouts/header.php'; ?>

<div class="min-vh-100 d-flex align-items-center justify-content-center"
    style="background-image: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url('../img/trees_background.jpg'); 
    background-size: cover; 
    background-position: center;" >

    <div class="container-fluid p-5" style="background-color: #ffffff5e;">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                
                <!------- PROFILE AVATAR ------------------------------------------------------------------------------------>
                <div class="text-center mb-4">
                    <div class="bg-white rounded-circle mx-auto d-flex align-items-center justify-content-center shadow-lg" 
                         style="width: 120px; height: 120px;">
                        <i class="bi bi-person-fill text-secondary" style="font-size: 4rem;"></i>
                    </div>
                </div>

                <!------- HEADING ---------------------------------------------------------------------------------------------->
                <h2 class="text-center fw-bold mb-5">
                    Refer Friends. Earn Points. Win Together.
                </h2>

                <!------- REFERRAL CARD ---------------------------------------------------------------------------------------->
                <div class="card border-0 shadow-lg rounded-4" style="background-color: #f5f5f0;">
                    <div class="card-body p-4 p-md-5">
                        <div class="row">
                            
                            <!------- FORM ----------------------------------------------------------------------------------->
                            <div class="col-12 col-md-7 mb-4 mb-md-0">
                                
                                <!--- USER REFERRAL CODE --------------------------------------------------------------------------->
                                <div class="mb-4">
                                    <label class="form-label fw-bold mb-3 fs-5">
                                        Your code:
                                    </label>
                                    <input type="text" 
                                    class="form-control border-0 rounded-4 py-3" 
                                    value="654321" 
                                    readonly
                                    style="font-size: 1.2rem; letter-spacing: 0.2em; background-color: #D9D9D94D;">
                                </div>

                                <!--- FRIEND'S CODE --------------------------------------------------------------------->
                                <div class="mb-4">
                                    <label class="form-label fw-bold mb-3 fs-5"">
                                        Enter your friend's code:
                                    </label>
                                    <input type="text" 
                                    class="form-control border-0 rounded-4 py-3" 
                                    placeholder="******" 
                                    style="font-size: 1.2rem; letter-spacing: 0.2em; background-color: #D9D9D94D;">
                                </div>

                                <!--- CONFIRM BUTTON ------------------------------------------------------------------------->
                                <div class="float-end">
                                    <button type="submit" 
                                            class="btn text-muted border-0 rounded-3 px-5 py-2" style="background-color: #F1B24A;">
                                        Confirm
                                    </button>
                                </div>

                            </div>

                            <!--- GIFT IMAGE --------------------------------------------------------------------------->
                            <div class="col-12 col-md-5 d-flex align-items-center justify-content-center">
                                <img src="../img/gift.png" 
                                     alt="Gift Boxes" 
                                     class="img-fluid" 
                                     style="max-width: 300px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>