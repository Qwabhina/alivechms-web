import { ref, type InjectionKey, type Ref } from 'vue'

export type AccordionValue = string | number

/**
 * Context provided by ChAccordion to all ChAccordionItem children.
 *
 * Uses `Ref` rather than `ComputedRef` so the fallback constant below
 * can be built from plain `ref()` calls without lying to TypeScript.
 * `ComputedRef<T>` extends `Ref<T>`, so the computed refs provided by
 * ChAccordion still satisfy this interface — no cast required.
 */
export interface AccordionContext {
   /** Returns true if the given item value is currently open */
   isOpen: (value: AccordionValue) => boolean
   /** Toggles the given item value open or closed */
   toggle: (value: AccordionValue) => void
   /** Whether the accordion allows multiple items open simultaneously */
   multiple: Ref<boolean>
}

/**
 * Honest fallback used by ChAccordionItem when rendered outside a
 * ChAccordion. Uses real Vue refs and no-op functions rather than
 * casts or plain-object duck-typing.
 */
export const defaultAccordionContext: AccordionContext = {
   isOpen: () => false,
   toggle: () => { },
   multiple: ref(false),
}

/** Typed injection key — import this in ChAccordionItem */
export const ACCORDION_KEY: InjectionKey<AccordionContext> = Symbol('ChAccordion')