<?php
$pageTitle = 'Members Management';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
   <!-- Page Header -->
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <h1 class="h3 mb-1">
            <i class="bi bi-people-fill me-2"></i>Members
         </h1>
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

   <!-- Distribution Charts -->
   <div class="row mb-4" id="distributionCharts">
      <div class="col-md-6">
         <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
               <h6 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Gender Distribution</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 250px;">
               <canvas id="genderChart"></canvas>
            </div>
         </div>
      </div>
      <div class="col-md-6">
         <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
               <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Age Distribution</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 250px;">
               <canvas id="ageChart"></canvas>
            </div>
         </div>
      </div>
   </div>

   <!-- Members Table Card -->
   <div class="card shadow-sm">
      <div class="card-header bg-white border-bottom">
         <div class="row align-items-center">
            <div class="col-md-6">
               <h5 class="mb-0">
                  <i class="bi bi-table me-2"></i>All Members
                  <span class="badge bg-primary ms-2" id="total-members-count">0</span>
               </h5>
            </div>
            <div class="col-md-6 text-end">
               <button class="btn btn-sm btn-outline-secondary" id="refreshMemberGrid" title="Refresh">
                  <i class="bi bi-arrow-clockwise"></i> Refresh
               </button>
            </div>
         </div>
      </div>

      <div class="card-body">
         <!-- QMGrid Table Container -->
         <div id="membersTable"></div>
      </div>
   </div>
</div>
</main>

<!-- Member Modal with Stepper -->
<div class="modal fade" id="memberModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
   <div class="modal-dialog modal-xl modal-dialog-scrollable">
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

               <form id="memberForm" novalidate>
                  <input type="hidden" id="memberId" name="memberId">

                  <!-- Step 1: Basic Info -->
                  <div class="stepper-content" data-step="0">
                     <div class="row">
                        <div class="col-12 text-center mb-4">
                           <div class="profile-upload-zone" id="profileDropzone">
                              <div class="upload-placeholder" id="uploadPlaceholder">
                                 <i class="bi bi-camera fs-1"></i>
                                 <div class="small mt-2">Click or drag to upload photo</div>
                                 <div class="text-muted" style="font-size: 0.75rem;">Max 5MB • JPG, PNG, GIF</div>
                              </div>
                              <img id="profilePreview" class="d-none" alt="Profile">
                           </div>
                           <input type="file" id="profilePictureInput" accept="image/*" class="d-none">
                           <button type="button" class="btn btn-sm btn-outline-danger mt-2 d-none" id="removePhotoBtn">
                              <i class="bi bi-trash me-1"></i>Remove Photo
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
                           <div class="form-text">Optional: Used to calculate age</div>
                        </div>
                     </div>
                  </div>

                  <!-- Step 2: Contact Info -->
                  <div class="stepper-content d-none" data-step="1">
                     <div class="row">
                        <div class="col-md-6 mb-3">
                           <label class="form-label">Email Address <span class="text-danger">*</span></label>
                           <input type="email" class="form-control" id="email" required>
                           <div class="form-text">Used for communication and login</div>
                        </div>
                        <div class="col-md-6 mb-3">
                           <label class="form-label">Residential Address</label>
                           <input type="text" class="form-control" id="address" placeholder="Street, City, Region">
                        </div>
                        <div class="col-12 mb-3">
                           <label class="form-label">Phone Numbers</label>
                           <div id="phoneContainer">
                              <div class="phone-row mb-2">
                                 <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="text" class="form-control phone-input" placeholder="e.g., 0241234567">
                                    <button type="button" class="btn btn-outline-danger remove-phone d-none">
                                       <i class="bi bi-trash"></i>
                                    </button>
                                 </div>
                              </div>
                           </div>
                           <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addPhoneBtn">
                              <i class="bi bi-plus-circle me-1"></i>Add Another Phone
                           </button>
                        </div>
                     </div>
                  </div>

                  <!-- Step 3: Personal Info -->
                  <div class="stepper-content d-none" data-step="2">
                     <div class="row">
                        <div class="col-md-6 mb-3">
                           <label class="form-label">Occupation</label>
                           <input type="text" class="form-control" id="occupation" placeholder="e.g., Teacher, Engineer">
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
                           <input type="text" class="form-control" id="educationLevel" placeholder="e.g., Bachelor's Degree, High School">
                        </div>
                     </div>
                  </div>

                  <!-- Step 4: Family Info -->
                  <div class="stepper-content d-none" data-step="3">
                     <div class="row">
                        <div class="col-12 mb-3">
                           <div class="alert alert-info">
                              <i class="bi bi-info-circle me-2"></i>
                              <strong>Family Assignment:</strong> Assign this member to an existing family or leave unassigned.
                           </div>
                        </div>
                        <div class="col-md-12 mb-3">
                           <label class="form-label">Family</label>
                           <select class="form-select" id="familySelect">
                              <option value="">No Family</option>
                           </select>
                           <div class="form-text">Members can be grouped into families for better organization</div>
                        </div>
                     </div>
                  </div>

                  <!-- Step 5: Login Info -->
                  <div class="stepper-content d-none" data-step="4">
                     <div class="row">
                        <div class="col-12 mb-3">
                           <div class="alert alert-info">
                              <i class="bi bi-info-circle me-2"></i>
                              <strong>Optional:</strong> Login credentials allow the member to access the system. You can add these later if needed.
                           </div>
                           <div class="form-check form-switch">
                              <input class="form-check-input" type="checkbox" id="enableLogin">
                              <label class="form-check-label fw-semibold" for="enableLogin">
                                 Enable login access for this member
                              </label>
                           </div>
                        </div>
                        <div id="loginFields" class="row d-none">
                           <div class="col-md-6 mb-3">
                              <label class="form-label">Username</label>
                              <input type="text" class="form-control" id="username" autocomplete="off">
                              <div class="form-text">Unique username for login</div>
                           </div>
                           <div class="col-md-6 mb-3">
                              <label class="form-label">Password</label>
                              <input type="password" class="form-control" id="password" autocomplete="new-password">
                              <div class="form-text">Minimum 8 characters</div>
                           </div>
                           <div class="col-md-12 mb-3">
                              <label class="form-label">Role</label>
                              <select class="form-select" id="roleSelect">
                                 <option value="">Select Role</option>
                              </select>
                              <div class="form-text">Determines member's permissions in the system</div>
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
   <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
         <div class="modal-header border-0 p-0">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" style="z-index: 10;" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body p-0" id="viewMemberContent">
            <div class="text-center py-5">
               <div class="spinner-border text-primary" role="status"></div>
               <p class="text-muted mt-2">Loading member details...</p>
            </div>
         </div>
         <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
               <i class="bi bi-x-circle me-1"></i>Close
            </button>
            <button type="button" class="btn btn-primary" id="editFromViewBtn" data-permission="edit_members">
               <i class="bi bi-pencil me-1"></i>Edit Member
            </button>
         </div>
      </div>
   </div>
</div>

<!-- Include JavaScript -->
<script src="../assets/js/modules/members.js"></script>
<script src="../assets/js/core/qmgrid-helper.js"></script>

<style>
   /* Stepper Styles */
   .stepper {
      display: flex;
      justify-content: space-between;
      position: relative;
      margin-bottom: 2rem;
   }

   .stepper::before {
      content: '';
      position: absolute;
      top: 20px;
      left: 0;
      right: 0;
      height: 2px;
      background: #dee2e6;
      z-index: 0;
   }

   .stepper-step {
      flex: 1;
      text-align: center;
      position: relative;
      z-index: 1;
      cursor: pointer;
   }

   .step-number {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: white;
      border: 2px solid #dee2e6;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      margin: 0 auto 8px;
      transition: all 0.3s;
   }

   .step-label {
      font-size: 0.875rem;
      color: #6c757d;
      font-weight: 500;
   }

   .stepper-step.active .step-number {
      background: var(--bs-primary);
      color: white;
      border-color: var(--bs-primary);
      box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.15);
   }

   .stepper-step.active .step-label {
      color: var(--bs-primary);
      font-weight: 600;
   }

   .stepper-step.completed .step-number {
      background: var(--bs-success);
      color: white;
      border-color: var(--bs-success);
   }

   .stepper-step.completed .step-number::after {
      content: '✓';
      position: absolute;
   }

   .stepper-step.completed .step-label {
      color: var(--bs-success);
   }

   /* Profile Upload Zone */
   .profile-upload-zone {
      width: 150px;
      height: 150px;
      margin: 0 auto;
      border: 3px dashed #dee2e6;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s;
      overflow: hidden;
      position: relative;
   }

   .profile-upload-zone:hover {
      border-color: var(--bs-primary);
      background: #f8f9fa;
   }

   .profile-upload-zone img {
      width: 100%;
      height: 100%;
      object-fit: cover;
   }

   .upload-placeholder {
      text-align: center;
      color: #6c757d;
   }

   /* Phone Row Styles */
   .phone-row {
      animation: slideIn 0.3s ease-out;
   }

   @keyframes slideIn {
      from {
         opacity: 0;
         transform: translateY(-10px);
      }

      to {
         opacity: 1;
         transform: translateY(0);
      }
   }

   /* Stat Card Styles */
   .stat-card {
      border: none;
      transition: transform 0.2s, box-shadow 0.2s;
   }

   .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
   }

   .stat-icon {
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
   }

   /* Responsive adjustments */
   @media (max-width: 768px) {
      .stepper {
         flex-wrap: wrap;
      }

      .stepper-step {
         flex-basis: 33.333%;
         margin-bottom: 1rem;
      }

      .stepper::before {
         display: none;
      }
   }
</style>

<?php require_once '../includes/footer.php'; ?>