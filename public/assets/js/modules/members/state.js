/**
 * Member State Management
 */

export class MemberState {
   constructor() {
      this.currentMemberId = null;
      this.isEditMode = false;
      this.currentStep = 0;
      this.totalSteps = 3;
      this.profilePictureFile = null;
      this.familiesData = [];
      this.rolesData = [];
      this.maritalStatusData = [];
      this.educationLevelData = [];
   }

   reset() {
      this.currentMemberId = null;
      this.isEditMode = false;
      this.currentStep = 0;
      this.profilePictureFile = null;
   }

   setEditMode(memberId) {
      this.isEditMode = true;
      this.currentMemberId = memberId;
   }

   nextStep() {
      if (this.currentStep < this.totalSteps - 1) {
         this.currentStep++;
         return true;
      }
      return false;
   }

   prevStep() {
      if (this.currentStep > 0) {
         this.currentStep--;
         return true;
      }
      return false;
   }

   goToStep(step) {
      if (step >= 0 && step < this.totalSteps) {
         this.currentStep = step;
         return true;
      }
      return false;
   }
}
