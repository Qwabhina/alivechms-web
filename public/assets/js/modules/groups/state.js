/**
 * Groups State Management
 */

export class GroupState {
   constructor() {
      this.currentGroupId = null;
      this.isEditMode = false;
      this.membersData = [];
      this.groupTypesData = [];
      this.leaderChoices = null;
      this.typeChoices = null;
      this.addMemberChoices = null;
   }

   reset() {
      this.currentGroupId = null;
      this.isEditMode = false;
   }

   setEditMode(groupId) {
      this.currentGroupId = groupId;
      this.isEditMode = true;
   }
}
