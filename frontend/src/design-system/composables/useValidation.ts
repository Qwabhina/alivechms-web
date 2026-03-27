/**
 * @file useValidation.ts
 * @path /frontend/src/design-system/composables/useValidation.ts
 * @description Composable for form validation with built-in validators.
 */

import { computed, reactive, ref, type Ref } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

export interface ValidationRule {
  rule: ValidatorFn | BuiltinRule
  message: string
  skipIfEmpty?: boolean
}

export type BuiltinRule =
  | 'required'
  | 'email'
  | 'url'
  | 'phone'
  | 'number'
  | 'alpha'
  | 'alphanumeric'

export type ValidatorFn = (value: unknown, formValues?: Record<string, unknown>) => boolean | Promise<boolean>

export interface FieldState {
  value: unknown
  initialValue: unknown
  touched: boolean
  dirty: boolean
  validating: boolean
  error: string | null
  valid: boolean
}

export interface UseValidationOptions {
  initialValues?: Record<string, unknown>
  rules?: Record<string, ValidationRule[]>
  validateOnInput?: boolean
  validateOnBlur?: boolean
  validateOnChange?: boolean
}

// ─── Built-in validators ─────────────────────────────────────────────────────

const builtinValidators: Record<BuiltinRule, ValidatorFn> = {
  required: (value) => {
    if (typeof value === 'string') return value.trim().length > 0
    if (Array.isArray(value)) return value.length > 0
    return value !== null && value !== undefined && value !== ''
  },
  email: (value) => {
    if (typeof value !== 'string') return false
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
  },
  url: (value) => {
    if (typeof value !== 'string') return false
    try {
      new URL(value)
      return true
    } catch {
      return false
    }
  },
  phone: (value) => {
    if (typeof value !== 'string') return false
    return /^\+?[0-9]{10,14}$/.test(value.replace(/[\s()-]/g, ''))
  },
  number: (value) => {
    if (typeof value === 'number') return !isNaN(value)
    if (typeof value === 'string') return !isNaN(Number(value)) && value.trim() !== ''
    return false
  },
  alpha: (value) => {
    if (typeof value !== 'string') return false
    return /^[a-zA-Z]+$/.test(value)
  },
  alphanumeric: (value) => {
    if (typeof value !== 'string') return false
    return /^[a-zA-Z0-9]+$/.test(value)
  },
}

// ─── Validator factory functions ─────────────────────────────────────────────

export function validators() {
  return {
    required: (message: string): ValidationRule => ({ rule: 'required', message }),
    email: (message: string = 'Invalid email address'): ValidationRule => ({ rule: 'email', message }),
    url: (message: string = 'Invalid URL'): ValidationRule => ({ rule: 'url', message }),
    phone: (message: string = 'Invalid phone number'): ValidationRule => ({ rule: 'phone', message }),
    number: (message: string = 'Must be a number'): ValidationRule => ({ rule: 'number', message }),
    alpha: (message: string = 'Only letters allowed'): ValidationRule => ({ rule: 'alpha', message }),
    alphanumeric: (message: string = 'Only letters and numbers allowed'): ValidationRule => ({
      rule: 'alphanumeric',
      message,
    }),
    custom: (validator: ValidatorFn, message: string, skipIfEmpty = true): ValidationRule => ({
      rule: validator,
      message,
      skipIfEmpty,
    }),
    async: (validator: ValidatorFn, message: string): ValidationRule => ({
      rule: validator,
      message,
      skipIfEmpty: true,
    }),
  }
}

// ─── useValidation composable ────────────────────────────────────────────────

export function useValidation(options: UseValidationOptions = {}) {
  const {
    initialValues = {},
    rules = {},
    validateOnInput = true,
    validateOnBlur = true,
    validateOnChange = true,
  } = options

  // Create reactive field states
  const fields: Record<string, FieldState> = reactive({})
  const values: Record<string, unknown> = reactive({ ...initialValues })
  const errors: Record<string, string | null> = reactive({})

  // Initialize fields
  Object.keys(initialValues).forEach((key) => {
    fields[key] = {
      value: initialValues[key] ?? null,
      initialValue: initialValues[key] ?? null,
      touched: false,
      dirty: false,
      validating: false,
      error: null,
      valid: true,
    }
    errors[key] = null
  })

  // Computed form state
  const valid = computed(() => Object.values(errors).every((e) => e === null))
  const touched = computed(() => Object.values(fields).some((f) => f.touched))
  const dirty = computed(() => Object.values(fields).some((f) => f.dirty))
  const validating = computed(() => Object.values(fields).some((f) => f.validating))

  // ─── Methods ────────────────────────────────────────────────────────────────

  function register(name: string, fieldRules: ValidationRule[] = []) {
    if (!fields[name]) {
      fields[name] = {
        value: values[name] ?? null,
        initialValue: values[name] ?? null,
        touched: false,
        dirty: false,
        validating: false,
        error: null,
        valid: true,
      }
      errors[name] = null
    }

    const field = fields[name]

    async function validateField(): Promise<boolean> {
      const fieldRulesToUse = fieldRules || rules[name] || []
      if (!fieldRulesToUse.length) return true

      field.validating = true
      field.error = null
      field.valid = true

      for (const { rule, message, skipIfEmpty } of fieldRulesToUse) {
        if (skipIfEmpty && (field.value === null || field.value === '' || field.value === undefined)) {
          continue
        }

        const validator = typeof rule === 'string' ? builtinValidators[rule] : rule

        try {
          const result = await validator(field.value, values)
          if (!result) {
            field.error = message
            field.valid = false
            errors[name] = message
            field.validating = false
            return false
          }
        } catch (e) {
          console.error(`Validation error for field "${name}":`, e)
          field.error = 'Validation failed'
          field.valid = false
          errors[name] = 'Validation failed'
          field.validating = false
          return false
        }
      }

      field.error = null
      field.valid = true
      errors[name] = null
      field.validating = false
      return true
    }

    function setValue(newValue: unknown, shouldValidate = validateOnInput) {
      field.value = newValue
      values[name] = newValue
      field.dirty = field.value !== field.initialValue

      if (shouldValidate) {
        validateField()
      }
    }

    function setTouched(isTouched: boolean, shouldValidate = validateOnBlur) {
      field.touched = isTouched
      if (shouldValidate && isTouched) {
        validateField()
      }
    }

    function reset() {
      field.value = field.initialValue
      values[name] = field.initialValue
      field.touched = false
      field.dirty = false
      field.error = null
      field.valid = true
      errors[name] = null
    }

    return {
      value: computed(() => field.value),
      error: computed(() => (field.touched ? field.error : null)),
      touched: computed(() => field.touched),
      dirty: computed(() => field.dirty),
      valid: computed(() => field.valid),
      validating: computed(() => field.validating),
      setValue,
      setTouched,
      reset,
      validate: validateField,
    }
  }

  async function validate(): Promise<boolean> {
    const results = await Promise.all(
      Object.keys(fields).map(async (name) => {
        const fieldRules = rules[name] || []
        if (!fieldRules.length) return true

        const field = fields[name]
        if (field) field.touched = true
        return await register(name, fieldRules).validate()
      })
    )
    return results.every((r) => r)
  }

  function reset() {
    Object.keys(fields).forEach((name) => {
      const field = fields[name]
      if (field) {
        field.value = field.initialValue
        values[name] = field.initialValue
        field.touched = false
        field.dirty = false
        field.error = null
        field.valid = true
        errors[name] = null
      }
    })
  }

  function setValues(newValues: Record<string, unknown>) {
    Object.entries(newValues).forEach(([key, value]) => {
      if (fields[key]) {
        fields[key].value = value
        values[key] = value
        fields[key].dirty = value !== fields[key].initialValue
      }
    })
  }

  return {
    values,
    errors,
    fields,
    valid,
    touched,
    dirty,
    validating,
    register,
    validate,
    reset,
    setValues,
  }
}

// ─── useForm composable ──────────────────────────────────────────────────────

export function useForm(
  options: UseValidationOptions & {
    onSubmit?: (values: Record<string, unknown>) => Promise<void> | void
  } = {}
) {
  const { onSubmit, ...validationOptions } = options
  const validation = useValidation(validationOptions)
  const submitErrors = ref<string | null>(null)
  const isSubmitting = ref(false)

  async function handleSubmit(event?: Event) {
    event?.preventDefault()

    submitErrors.value = null
    isSubmitting.value = true

    const isValid = await validation.validate()

    if (!isValid) {
      isSubmitting.value = false
      Object.keys(validation.fields).forEach((name) => {
        const field = validation.fields[name]
        if (field) field.touched = true
      })
      return
    }

    try {
      await onSubmit?.(validation.values)
    } catch (error) {
      submitErrors.value = error instanceof Error ? error.message : 'Submission failed'
    } finally {
      isSubmitting.value = false
    }
  }

  return {
    ...validation,
    submitErrors,
    isSubmitting,
    handleSubmit,
  }
}
