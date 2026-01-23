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
         <div class="modal-header bg-primary bg-opacity-10 border-0">
            <h5 class="modal-title" id="memberModalTitle">
               <i class="bi bi-person-plus me-2"></i>Add New Member
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body px-4">
            <div id="memberStepper">
               <!-- Stepper Header - Reduced to 3 steps -->
               <div class="stepper mb-4">
                  <div class="stepper-step active" data-step="0">
                     <div class="step-number">1</div>
                     <div class="step-label">Personal Details</div>
                  </div>
                  <div class="stepper-step" data-step="1">
                     <div class="step-number">2</div>
                     <div class="step-label">Contact & Family</div>
                  </div>
                  <div class="stepper-step" data-step="2">
                     <div class="step-number">3</div>
                     <div class="step-label">Account Setup</div>
                  </div>
               </div>

               <form id="memberForm" novalidate>
                  <input type="hidden" id="memberId" name="memberId">

                  <!-- Step 1: Personal Details (Basic + Personal Info combined) -->
                  <div class="stepper-content" data-step="0">
                     <!-- Profile Photo Section -->
                     <div class="text-center mb-4 pb-3 border-bottom">
                        <div class="profile-upload-zone mx-auto" id="profileDropzone">
                           <div class="upload-placeholder" id="uploadPlaceholder">
                              <i class="bi bi-camera fs-1 text-primary"></i>
                              <div class="small mt-2 fw-medium">Upload Photo</div>
                              <div class="text-muted" style="font-size: 0.7rem;">JPG, PNG, GIF • Max 5MB</div>
                           </div>
                           <img id="profilePreview" class="d-none" alt="Profile">
                        </div>
                        <input type="file" id="profilePictureInput" accept="image/*" class="d-none">
                        <button type="button" class="btn btn-sm btn-outline-danger mt-2 d-none" id="removePhotoBtn">
                           <i class="bi bi-x-circle me-1"></i>Remove
                        </button>
                     </div>

                     <!-- Name Fields -->
                     <div class="section-header mb-3">
                        <i class="bi bi-person-badge text-primary me-2"></i>
                        <span class="fw-semibold">Name Information</span>
                     </div>
                     <div class="row g-3 mb-4">
                        <div class="col-md-4">
                           <label class="form-label">First Name <span class="text-danger">*</span></label>
                           <input type="text" class="form-control" id="firstName" placeholder="Enter first name" required>
                        </div>
                        <div class="col-md-4">
                           <label class="form-label">Family Name <span class="text-danger">*</span></label>
                           <input type="text" class="form-control" id="familyName" placeholder="Enter family name" required>
                        </div>
                        <div class="col-md-4">
                           <label class="form-label">Other Names</label>
                           <input type="text" class="form-control" id="otherNames" placeholder="Middle name, etc.">
                        </div>
                     </div>

                     <!-- Personal Details -->
                     <div class="section-header mb-3">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        <span class="fw-semibold">Personal Information</span>
                     </div>
                     <div class="row g-3">
                        <div class="col-md-4">
                           <label class="form-label">Gender <span class="text-danger">*</span></label>
                           <select class="form-select" id="gender" required>
                              <option value="">Select gender</option>
                              <option value="Male">Male</option>
                              <option value="Female">Female</option>
                              <option value="Other">Other</option>
                           </select>
                        </div>
                        <div class="col-md-4">
                           <label class="form-label">Date of Birth</label>
                           <input type="text" class="form-control" id="dateOfBirth" placeholder="Select date">
                        </div>
                        <div class="col-md-4">
                           <label class="form-label">Marital Status</label>
                           <select class="form-select" id="maritalStatus">
                              <option value="">Select status</option>
                              <option value="Single">Single</option>
                              <option value="Married">Married</option>
                              <option value="Divorced">Divorced</option>
                              <option value="Widowed">Widowed</option>
                           </select>
                        </div>
                        <div class="col-md-6">
                           <label class="form-label">Occupation</label>
                           <input type="text" class="form-control" id="occupation" placeholder="e.g., Teacher, Engineer">
                        </div>
                        <div class="col-md-6">
                           <label class="form-label">Education Level</label>
                           <select class="form-select" id="educationLevel">
                              <option value="">Select education level</option>
                              <option value="No Formal Education">No Formal Education</option>
                              <option value="Primary School">Primary School</option>
                              <option value="Junior High School">Junior High School</option>
                              <option value="Senior High School">Senior High School</option>
                              <option value="Vocational/Technical">Vocational/Technical</option>
                              <option value="Diploma">Diploma</option>
                              <option value="Bachelor's Degree">Bachelor's Degree</option>
                              <option value="Master's Degree">Master's Degree</option>
                              <option value="Doctorate">Doctorate</option>
                              <option value="Other">Other</option>
                           </select>
                        </div>
                     </div>
                  </div>

                  <!-- Step 2: Contact & Family Info -->
                  <div class="stepper-content d-none" data-step="1">
                     <!-- Contact Information -->
                     <div class="section-header mb-3">
                        <i class="bi bi-telephone text-primary me-2"></i>
                        <span class="fw-semibold">Contact Information</span>
                     </div>
                     <div class="row g-3 mb-4">
                        <div class="col-md-6">
                           <label class="form-label">Email Address <span class="text-danger">*</span></label>
                           <div class="input-group">
                              <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                              <input type="email" class="form-control" id="email" placeholder="member@example.com" required>
                           </div>
                        </div>
                        <div class="col-md-6">
                           <label class="form-label">Residential Address</label>
                           <div class="input-group">
                              <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                              <input type="text" class="form-control" id="address" placeholder="Street, City, Region">
                           </div>
                        </div>
                        <div class="col-12">
                           <label class="form-label">Phone Numbers</label>
                           <div id="phoneContainer">
                              <div class="phone-row mb-2">
                                 <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="text" class="form-control phone-input" placeholder="e.g., 0241234567">
                                    <span class="input-group-text bg-success text-white" title="Primary">
                                       <i class="bi bi-star-fill" style="font-size: 0.7rem;"></i>
                                    </span>
                                 </div>
                              </div>
                           </div>
                           <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addPhoneBtn">
                              <i class="bi bi-plus-circle me-1"></i>Add Phone
                           </button>
                        </div>
                     </div>

                     <!-- Family Assignment -->
                     <div class="section-header mb-3">
                        <i class="bi bi-people text-primary me-2"></i>
                        <span class="fw-semibold">Family Assignment</span>
                     </div>
                     <div class="row g-3">
                        <div class="col-12">
                           <div class="alert alert-light border mb-3">
                              <i class="bi bi-lightbulb text-warning me-2"></i>
                              Assign this member to an existing family for better organization, or leave unassigned.
                           </div>
                        </div>
                        <div class="col-md-12">
                           <label class="form-label">Select Family</label>
                           <select class="form-select" id="familySelect">
                              <option value="">No Family Assignment</option>
                           </select>
                        </div>
                     </div>
                  </div>

                  <!-- Step 3: Account Setup (Login Info) -->
                  <div class="stepper-content d-none" data-step="2">
                     <div class="section-header mb-3">
                        <i class="bi bi-shield-lock text-primary me-2"></i>
                        <span class="fw-semibold">System Access</span>
                     </div>

                     <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                           <div class="form-check form-switch">
                              <input class="form-check-input" type="checkbox" id="enableLogin" style="width: 3em; height: 1.5em;">
                              <label class="form-check-label ms-2" for="enableLogin">
                                 <span class="fw-semibold">Enable System Login</span>
                                 <div class="text-muted small">Allow this member to log into the church management system</div>
                              </label>
                           </div>
                        </div>
                     </div>

                     <div id="loginFields" class="d-none">
                        <div class="row g-3">
                           <div class="col-md-6">
                              <label class="form-label">Username <span class="text-danger">*</span></label>
                              <div class="input-group">
                                 <span class="input-group-text"><i class="bi bi-person"></i></span>
                                 <input type="text" class="form-control" id="username" placeholder="Choose a username" autocomplete="off">
                              </div>
                              <div class="form-text">Must be unique across all users</div>
                           </div>
                           <div class="col-md-6">
                              <label class="form-label">Password <span class="text-danger">*</span></label>
                              <div class="input-group">
                                 <span class="input-group-text"><i class="bi bi-key"></i></span>
                                 <input type="password" class="form-control" id="password" placeholder="Create a password" autocomplete="new-password">
                                 <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                 </button>
                              </div>
                              <div class="form-text">Minimum 8 characters recommended</div>
                           </div>
                           <div class="col-md-12">
                              <label class="form-label">System Role <span class="text-danger">*</span></label>
                              <select class="form-select" id="roleSelect">
                                 <option value="">Select a role</option>
                              </select>
                              <div class="form-text">Determines what the member can access and do in the system</div>
                           </div>
                        </div>
                     </div>

                     <div id="noLoginMessage" class="text-center py-4">
                        <i class="bi bi-person-badge fs-1 text-muted"></i>
                        <p class="text-muted mt-2 mb-0">This member will be registered without system login access.</p>
                        <p class="text-muted small">You can enable login access later by editing the member.</p>
                     </div>

                     <!-- Summary Preview -->
                     <div class="mt-4 pt-3 border-top">
                        <div class="section-header mb-3">
                           <i class="bi bi-check-circle text-success me-2"></i>
                           <span class="fw-semibold">Ready to Save</span>
                        </div>
                        <div class="alert alert-success border-0">
                           <i class="bi bi-info-circle me-2"></i>
                           Review the information and click <strong>Save Member</strong> to complete registration.
                        </div>
                     </div>
                  </div>
               </form>
            </div>
         </div>
         <div class="modal-footer bg-light border-0">
            <div class="d-flex justify-content-between w-100">
               <button type="button" class="btn btn-outline-secondary" id="prevStepBtn" disabled>
                  <i class="bi bi-arrow-left me-1"></i>Previous
               </button>
               <div>
                  <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
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

<!-- Include JavaScript - New Modular Structure -->
<script type="module" src="../assets/js/modules/members/index.js"></script>
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

   /* Section Header Styles */
   .section-header {
      display: flex;
      align-items: center;
      padding-bottom: 0.5rem;
      border-bottom: 1px solid #e9ecef;
      color: #495057;
   }

   .section-header i {
      font-size: 1.1rem;
   }

   /* Form Improvements */
   .form-label {
      font-weight: 500;
      color: #495057;
      margin-bottom: 0.4rem;
   }

   .form-control:focus,
   .form-select:focus {
      border-color: var(--bs-primary);
      box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
   }

   .input-group-text {
      background-color: #f8f9fa;
      border-color: #dee2e6;
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

      .profile-upload-zone {
         width: 120px;
         height: 120px;
      }
   }
</style>

<?php require_once '../includes/footer.php'; ?>