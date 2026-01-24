/**
 * Family State Management
 */

export class FamilyState {
   constructor() {
      this.currentFamilyId = null;
      this.isEditMode = false;
      this.membersData = [];
      this.headOfHouseholdChoices = null;
      this.addMemberChoices = null;
   }

   reset() {
      this.currentFamilyId = null;
      this.isEditMode = false;
   }

   setEditMode(familyId) {
      this.isEditMode = true;
      this.currentFamilyId = familyId;
   }
}
