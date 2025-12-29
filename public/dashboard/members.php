<?php
$pageTitle = 'Members Management';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">Members</h1>
         <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
               <li class="breadcrumb-item"><a href="../dashboard/">Dashboard</a></li>
               <li class="breadcrumb-item active">Members</li>
            </ol>
         </nav>
      </div>
      <button class="btn btn-primary" id="addMemberBtn" data-permission="create_members">
         <i class="bi bi-plus-circle me-2"></i>Add Member
      </button>
   </div>

   <!-- Stats Cards -->
   <div class="row" id="statsCards">
      <div class="col-12 text-center py-5">
         <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
         </div>
         <p class="text-muted mt-2">Loading member statistics...</p>
      </div>
   </div>

   <!-- Members Table -->
   <div class="card mt-4">
      <div class="card-header">
         <h5 class="mb-0"><i class="bi bi-people me-2"></i>All Members</h5>
      </div>
      <div class="card-body">
         <div class="d-flex justify-content-end mb-3 gap-2">
            <button class="btn btn-success btn-sm" onclick="membersGrid.download('xlsx', 'members.xlsx')">
               <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </button>
            <button class="btn btn-danger btn-sm" onclick="membersGrid.download('pdf', 'members.pdf', {orientation:'landscape', title:'Members List'})">
               <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </button>
            <button class="btn btn-primary btn-sm" onclick="membersGrid.print()">
               <i class="bi bi-printer me-1"></i>Print
            </button>
            <button class="btn btn-secondary btn-sm" onclick="membersGrid.setData()">
               <i class="bi bi-arrow-clockwise"></i>
            </button>
         </div>
         <div class="table-responsive">
            <div id="membersGrid"></div>
         </div>
      </div>
   </div>
</div>
</main>

<!-- Member Modal with Stepper -->
<div class="modal fade" id="memberModal" tabindex="-1" data-bs-backdrop="static">
   <div class="modal-dialog modal-xl">
      <div class="modal-content">
         <div class="modal-header border-0 pb-0">
            <h5 class="modal-title" id="memberModalTitle">Add New Member</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <div id="memberStepper">
               <!-- Stepper Header -->
               <div class="stepper mb-4">
                  <div class="stepper-step active" data-step="0">
                     <div class="step-number">1</div>
                     <div class="step-label">Basic Info</div>
                  </div>
                  <div class="stepper-step" data-step="1">
                     <div class="step-number">2</div>
                     <div class="step-label">Contact</div>
                  </div>
                  <div class="stepper-step" data-step="2">
                     <div class="step-number">3</div>
                     <div class="step-label">Personal</div>
                  </div>
                  <div class="stepper-step" data-step="3">
                     <div class="step-number">4</div>
                     <div class="step-label">Family</div>
                  </div>
                  <div class="stepper-step" data-step="4">
                     <div class="step-number">5</div>
                     <div class="step-label">Login</div>
                  </div>
               </div>

               <form id="memberForm">
                  <input type="hidden" id="memberId" name="memberId">

                  <!-- Step 1: Basic Info -->
                  <div class="stepper-content" data-step="0">
                     <div class="row">
                        <div class="col-12 text-center mb-4">
                           <div class="profile-upload-zone" id="profileDropzone">
                              <div class="upload-placeholder" id="uploadPlaceholder">
                                 <i class="bi bi-camera"></i>
                                 <div class="small">Upload Photo</div>
                              </div>
                              <img id="profilePreview" class="d-none" alt="Profile">
                           </div>
                           <input type="file" id="profilePictureInput" accept="image/*" class="d-none">
                           <button type="button" class="btn btn-sm btn-outline-danger mt-2 d-none" id="removePhotoBtn">
                              <i class="bi bi-trash me-1"></i>Remove
                           </button>
                        </div>
                        <div class="col-md-4 mb-3">
                           <label class="form-label">First Name <span class="text-danger">*</span></label>
                           <input type="text" class="form-control" id="firstName" required>
                        </div>
                        <div class="col-md-4 mb-3">
                           <label class="form-label">Family Name <span class="text-danger">*</span></label>
                           <input type="text" class="form-control" id="familyName" required>
                        </div>
                        <div class="col-md-4 mb-3">
                           <label class="form-label">Other Names</label>
                           <input type="text" class="form-control" id="otherNames">
                        </div>
                        <div class="col-md-6 mb-3">
                           <label class="form-label">Gender <span class="text-danger">*</span></label>
                           <select class="form-select" id="gender" required>
                              <option value="">Select Gender</option>
                              <option value="Male">Male</option>
                              <option value="Female">Female</option>
                              <option value="Other">Other</option>
                           </select>
                        </div>
                        <div class="col-md-6 mb-3">
                           <label class="form-label">Date of Birth</label>
                           <input type="text" class="form-control" id="dateOfBirth" placeholder="YYYY-MM-DD">
                        </div>
                     </div>
                  </div>

                  <!-- Step 2: Contact Info -->
                  <div class="stepper-content d-none" data-step="1">
                     <div class="row">
                        <div class="col-md-6 mb-3">
                           <label class="form-label">Email Address <span class="text-danger">*</span></label>
                           <input type="email" class="form-control" id="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                           <label class="form-label">Residential Address</label>
                           <input type="text" class="form-control" id="address">
                        </div>
                        <div class="col-12 mb-3">
                           <label class="form-label">Phone Numbers</label>
                           <div id="phoneContainer">
                              <div class="phone-row mb-2">
                                 <div class="input-group">
                                    <input type="text" class="form-control phone-input" placeholder="e.g., 0241234567">
                                    <button type="button" class="btn btn-outline-danger remove-phone d-none">
                                       <i class="bi bi-trash"></i>
                                    </button>
                                 </div>
                              </div>
                           </div>
                           <button type="button" class="btn btn-sm btn-outline-primary" id="addPhoneBtn">
                              <i class="bi bi-plus-circle me-1"></i>Add Phone
                           </button>
                        </div>
                     </div>
                  </div>

                  <!-- Step 3: Personal Info -->
                  <div class="stepper-content d-none" data-step="2">
                     <div class="row">
                        <div class="col-md-6 mb-3">
                           <label class="form-label">Occupation</label>
                           <input type="text" class="form-control" id="occupation">
                        </div>
                        <div class="col-md-6 mb-3">
                           <label class="form-label">Marital Status</label>
                           <select class="form-select" id="maritalStatus">
                              <option value="">Select Status</option>
                              <option value="Single">Single</option>
                              <option value="Married">Married</option>
                              <option value="Divorced">Divorced</option>
                              <option value="Widowed">Widowed</option>
                           </select>
                        </div>
                        <div class="col-md-12 mb-3">
                           <label class="form-label">Highest Education Level</label>
                           <input type="text" class="form-control" id="educationLevel">
                        </div>
                     </div>
                  </div>

                  <!-- Step 4: Family Info -->
                  <div class="stepper-content d-none" data-step="3">
                     <div class="row">
                        <div class="col-md-12 mb-3">
                           <label class="form-label">Family</label>
                           <select class="form-select" id="familySelect">
                              <option value="">No Family</option>
                           </select>
                        </div>
                     </div>
                  </div>

                  <!-- Step 5: Login Info -->
                  <div class="stepper-content d-none" data-step="4">
                     <div class="row">
                        <div class="col-12 mb-3">
                           <div class="alert alert-info">
                              <i class="bi bi-info-circle me-2"></i>
                              Login credentials are optional. You can add them later.
                           </div>
                           <div class="form-check">
                              <input class="form-check-input" type="checkbox" id="enableLogin">
                              <label class="form-check-label" for="enableLogin">
                                 Enable login access for this member
                              </label>
                           </div>
                        </div>
                        <div id="loginFields" class="row d-none">
                           <div class="col-md-6 mb-3">
                              <label class="form-label">Username</label>
                              <input type="text" class="form-control" id="username">
                           </div>
                           <div class="col-md-6 mb-3">
                              <label class="form-label">Password</label>
                              <input type="password" class="form-control" id="password">
                              <small class="text-muted">Min 8 characters</small>
                           </div>
                           <div class="col-md-12 mb-3">
                              <label class="form-label">Role</label>
                              <select class="form-select" id="roleSelect">
                                 <option value="">Select Role</option>
                              </select>
                           </div>
                        </div>
                     </div>
                  </div>
               </form>
            </div>
         </div>
         <div class="modal-footer border-0">
            <button type="button" class="btn btn-outline-secondary" id="prevStepBtn" disabled>
               <i class="bi bi-arrow-left me-1"></i>Previous
            </button>
            <button type="button" class="btn btn-primary" id="nextStepBtn">
               Next<i class="bi bi-arrow-right ms-1"></i>
            </button>
            <button type="button" class="btn btn-success d-none" id="submitBtn">
               <i class="bi bi-check-circle me-1"></i>Save Member
            </button>
         </div>
      </div>
   </div>
</div>

<!-- View Member Modal with Tabs -->
<div class="modal fade" id="viewMemberModal" tabindex="-1">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Member Profile</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body p-0" id="viewMemberContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary" role="status"></div>
               <p class="text-muted mt-2">Loading member details...</p>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="editFromViewBtn" data-permission="edit_members">
               <i class="bi bi-pencil me-1"></i>Edit Member
            </button>
         </div>
      </div>
   </div>
</div>

<script src="../assets/js/modules/members.js"></script>
<?php require_once '../includes/footer.php'; ?>