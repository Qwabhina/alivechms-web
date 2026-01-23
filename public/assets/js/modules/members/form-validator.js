/**
 * Form Validation Component
 */

export class FormValidator {
   validateStep(step) {
      // Step 0: Personal Details
      if (step === 0) {
         const firstName = document.getElementById('firstName').value.trim();
         const familyName = document.getElementById('familyName').value.trim();
         const gender = document.getElementById('gender').value;

         if (!firstName) {
            Alerts.warning('First name is required');
            document.getElementById('firstName').focus();
            return false;
         }
         if (!familyName) {
            Alerts.warning('Family name is required');
            document.getElementById('familyName').focus();
            return false;
         }
         if (!gender) {
            Alerts.warning('Gender is required');
            document.getElementById('gender').focus();
            return false;
         }
      }

      // Step 1: Contact & Family
      if (step === 1) {
         const email = document.getElementById('email').value.trim();
         if (!email) {
            Alerts.warning('Email address is required');
            document.getElementById('email').focus();
            return false;
         }
         if (!this.isValidEmail(email)) {
            Alerts.warning('Please enter a valid email address');
            document.getElementById('email').focus();
            return false;
         }

         // Validate phone numbers
         const phoneInputs = document.querySelectorAll('.phone-input');
         for (const input of phoneInputs) {
            const phone = input.value.trim();
            if (phone && !this.isValidGhanaPhone(phone)) {
               Alerts.warning(`Invalid phone number format: ${phone}`);
               input.focus();
               return false;
            }
         }
      }

      // Step 2: Account Setup
      if (step === 2) {
         const enableLogin = document.getElementById('enableLogin').checked;
         if (enableLogin) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const role = document.getElementById('roleSelect').value;

            if (!username) {
               Alerts.warning('Username is required when login is enabled');
               document.getElementById('username').focus();
               return false;
            }
            if (!password) {
               Alerts.warning('Password is required when login is enabled');
               document.getElementById('password').focus();
               return false;
            }
            if (password.length < 8) {
               Alerts.warning('Password must be at least 8 characters');
               document.getElementById('password').focus();
               return false;
            }
            if (!role) {
               Alerts.warning('Role is required when login is enabled');
               document.getElementById('roleSelect').focus();
               return false;
            }
         }
      }

      return true;
   }

   isValidEmail(email) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
   }

   isValidGhanaPhone(phone) {
      // Ghana phone format: +233XXXXXXXXX or 0XXXXXXXXX
      return /^(\+?233|0)[2-5][0-9]{8}$/.test(phone);
   }
}
