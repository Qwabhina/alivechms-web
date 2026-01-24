/**
 * Form Stepper Component
 */

export class FormStepper {
   constructor(state) {
      this.state = state;
   }

   init() {
      this.update();
   }

   update() {
      // Update step indicators
      document.querySelectorAll('.stepper-step').forEach((step, idx) => {
         step.classList.remove('active', 'completed');
         if (idx < this.state.currentStep) step.classList.add('completed');
         if (idx === this.state.currentStep) step.classList.add('active');
      });

      // Update content visibility
      document.querySelectorAll('.stepper-content').forEach((content, idx) => {
         content.classList.toggle('d-none', idx !== this.state.currentStep);
      });

      // Update buttons
      const prevBtn = document.getElementById('prevStepBtn');
      const nextBtn = document.getElementById('nextStepBtn');
      const submitBtn = document.getElementById('submitBtn');

      if (prevBtn) prevBtn.disabled = this.state.currentStep === 0;
      if (nextBtn) nextBtn.classList.toggle('d-none', this.state.currentStep === this.state.totalSteps - 1);
      if (submitBtn) submitBtn.classList.toggle('d-none', this.state.currentStep !== this.state.totalSteps - 1);
   }

   reset() {
      this.state.currentStep = 0;
      this.update();
   }
}
