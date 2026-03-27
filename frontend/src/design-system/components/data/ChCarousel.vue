<script setup lang="ts">
/**
 * @component ChCarousel
 * @path /frontend/src/design-system/components/data/ChCarousel.vue
 * @description A slideshow component for cycling through images or content cards.
 * Supports touch swipe, keyboard navigation, autoplay, and thumbnails.
 *
 * ─── Design decisions ────────────────────────────────────────────────────────
 * - Touch-friendly swipe gestures for mobile
 * - Keyboard navigation (Arrow keys, Home, End)
 * - Optional autoplay with pause on hover
 * - Thumbnail navigation for quick access
 * - Smooth CSS transitions with hardware acceleration
 *
 * ─── Accessibility ───────────────────────────────────────────────────────────
 * - Uses `role="region"` with aria-label
 * - Slide buttons have aria-label for screen readers
 * - Current slide indicated with aria-current
 * - Keyboard accessible navigation
 *
 * ─── Usage ───────────────────────────────────────────────────────────────────
 * @example Basic usage
 * <ChCarousel :slides="[
 *   { src: '/img/slide1.jpg', alt: 'First slide' },
 *   { src: '/img/slide2.jpg', alt: 'Second slide' },
 *   { src: '/img/slide3.jpg', alt: 'Third slide' },
 * ]" />
 *
 * @example With autoplay
 * <ChCarousel
 *   :slides="slides"
 *   :autoplay="true"
 *   :autoplay-interval="5000"
 * />
 *
 * @example With thumbnails
 * <ChCarousel :slides="slides" :thumbnails="true" />
 *
 * @example Custom slide content
 * <ChCarousel v-model="currentSlide">
 *   <template #slide="{ slide, index }">
 *     <div class="custom-slide">
 *       <h3>{{ slide.title }}</h3>
 *       <p>{{ slide.description }}</p>
 *     </div>
 *   </template>
 * </ChCarousel>
 */

import { computed, ref, watch, onMounted, onUnmounted } from 'vue'

// ─── Types ────────────────────────────────────────────────────────────────────

/** A single slide in the carousel */
export interface CarouselSlide {
  /** Image source URL */
  src?: string
  /** Image alt text */
  alt?: string
  /** Optional title overlay */
  title?: string
  /** Optional description overlay */
  description?: string
  /** Optional custom content (if using slots) */
  content?: string
  /** Any additional data */
  [key: string]: unknown
}

// ─── Props ────────────────────────────────────────────────────────────────────

interface Props {
  /** Array of slides to display */
  slides?: CarouselSlide[]
  /** Currently active slide index — use v-model */
  modelValue?: number
  /** Show navigation arrows. Default: true */
  arrows?: boolean
  /** Show dot indicators. Default: true */
  dots?: boolean
  /** Show thumbnail navigation. Default: false */
  thumbnails?: boolean
  /** Enable autoplay. Default: false */
  autoplay?: boolean
  /** Autoplay interval in ms. Default: 5000 */
  autoplayInterval?: number
  /** Pause autoplay on hover. Default: true */
  pauseOnHover?: boolean
  /** Enable touch swipe. Default: true */
  swipe?: boolean
  /** Loop from last to first slide. Default: true */
  loop?: boolean
  /** Custom CSS class for the carousel */
  class?: string
  /** Aspect ratio for slides (e.g., '16/9', '4/3', '1/1'). Default: '16/9' */
  aspectRatio?: string
}

const props = withDefaults(defineProps<Props>(), {
  slides: () => [],
  modelValue: 0,
  arrows: true,
  dots: true,
  thumbnails: false,
  autoplay: false,
  autoplayInterval: 5000,
  pauseOnHover: true,
  swipe: true,
  loop: true,
  class: '',
  aspectRatio: '16/9',
})

// ─── Emits ────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
  'update:modelValue': [index: number]
  'slide-change': [index: number]
}>()

// ─── Local state ──────────────────────────────────────────────────────────────

const currentIndex = ref(props.modelValue)
const isHovering = ref(false)
const carouselRef = ref<HTMLElement | null>(null)

// Touch/swipe state
const touchStartX = ref(0)
const touchEndX = ref(0)
const isDragging = ref(false)

// ─── Computed ─────────────────────────────────────────────────────────────────

const totalSlides = computed(() => props.slides.length)

const hasMultipleSlides = computed(() => totalSlides.value > 1)

const isFirstSlide = computed(() => currentIndex.value === 0)

const isLastSlide = computed(() => currentIndex.value === totalSlides.value - 1)

const showArrows = computed(() => props.arrows && hasMultipleSlides.value)

const showDots = computed(() => props.dots && hasMultipleSlides.value)

const showThumbnails = computed(() => props.thumbnails && hasMultipleSlides.value)

const aspectRatioStyle = computed(() => ({
  aspectRatio: props.aspectRatio,
}))

// ─── Methods ──────────────────────────────────────────────────────────────────

function goToSlide(index: number) {
  let newIndex = index

  if (props.loop) {
    if (index < 0) newIndex = totalSlides.value - 1
    if (index >= totalSlides.value) newIndex = 0
  } else {
    newIndex = Math.max(0, Math.min(index, totalSlides.value - 1))
  }

  currentIndex.value = newIndex
  emit('update:modelValue', newIndex)
  emit('slide-change', newIndex)
}

function next() {
  goToSlide(currentIndex.value + 1)
}

function prev() {
  goToSlide(currentIndex.value - 1)
}

// Touch handlers
function handleTouchStart(e: TouchEvent) {
  if (!props.swipe) return
  const touch = e.touches[0]
  if (!touch) return
  touchStartX.value = touch.clientX
  isDragging.value = true
}

function handleTouchMove(e: TouchEvent) {
  if (!props.swipe || !isDragging.value) return
  const touch = e.touches[0]
  if (!touch) return
  touchEndX.value = touch.clientX
}

function handleTouchEnd() {
  if (!props.swipe || !isDragging.value) return
  isDragging.value = false

  const diff = touchStartX.value - touchEndX.value
  const threshold = 50 // Minimum swipe distance

  if (Math.abs(diff) > threshold) {
    if (diff > 0) {
      next() // Swiped left
    } else {
      prev() // Swiped right
    }
  }

  touchStartX.value = 0
  touchEndX.value = 0
}

// Keyboard navigation
function handleKeydown(e: KeyboardEvent) {
  switch (e.key) {
    case 'ArrowLeft':
      e.preventDefault()
      prev()
      break
    case 'ArrowRight':
      e.preventDefault()
      next()
      break
    case 'Home':
      e.preventDefault()
      goToSlide(0)
      break
    case 'End':
      e.preventDefault()
      goToSlide(totalSlides.value - 1)
      break
  }
}

// ─── Autoplay ────────────────────────────────────────────────────────────────

let autoplayTimer: ReturnType<typeof setTimeout> | null = null

function startAutoplay() {
  if (!props.autoplay || isHovering.value || !hasMultipleSlides.value) return

  autoplayTimer = setTimeout(() => {
    next()
    startAutoplay()
  }, props.autoplayInterval)
}

function stopAutoplay() {
  if (autoplayTimer) {
    clearTimeout(autoplayTimer)
    autoplayTimer = null
  }
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────

onMounted(() => {
  if (props.autoplay) {
    startAutoplay()
  }
})

onUnmounted(() => {
  stopAutoplay()
})

// Watch for hover state changes
watch(isHovering, () => {
  if (props.autoplay && props.pauseOnHover) {
    if (isHovering.value) {
      stopAutoplay()
    } else {
      startAutoplay()
    }
  }
})

// Sync with v-model
watch(() => props.modelValue, (newVal) => {
  currentIndex.value = newVal
})
</script>

<template>
  <div ref="carouselRef" :class="['ch-carousel', props.class]" :aria-label="'Carousel with ' + totalSlides + ' slides'"
    role="region" tabindex="0" @keydown="handleKeydown" @mouseenter="isHovering = true"
    @mouseleave="isHovering = false">
    <!-- Slides container -->
    <div class="ch-carousel__viewport">
      <div class="ch-carousel__track" :style="{ transform: `translateX(-${currentIndex * 100}%)` }"
        @touchstart="handleTouchStart" @touchmove="handleTouchMove" @touchend="handleTouchEnd">
        <div v-for="(slide, index) in slides" :key="index"
          :class="['ch-carousel__slide', { 'ch-carousel__slide--active': index === currentIndex }]"
          :style="aspectRatioStyle" :aria-hidden="index !== currentIndex ? 'true' : 'false'">
          <!-- Image slide -->
          <img v-if="slide.src" :src="slide.src" :alt="slide.alt || slide.title || ''" class="ch-carousel__image"
            loading="lazy" />

          <!-- Custom slot content -->
          <slot name="slide" :slide="slide" :index="index">
            <!-- Overlay content -->
            <div v-if="slide.title || slide.description" class="ch-carousel__overlay">
              <h3 v-if="slide.title" class="ch-carousel__title">{{ slide.title }}</h3>
              <p v-if="slide.description" class="ch-carousel__description">{{ slide.description }}</p>
            </div>
          </slot>
        </div>
      </div>
    </div>

    <!-- Previous arrow -->
    <button v-if="showArrows" class="ch-carousel__arrow ch-carousel__arrow--prev"
      :class="{ 'ch-carousel__arrow--hidden': !loop && isFirstSlide }" aria-label="Previous slide" @click="prev">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    </button>

    <!-- Next arrow -->
    <button v-if="showArrows" class="ch-carousel__arrow ch-carousel__arrow--next"
      :class="{ 'ch-carousel__arrow--hidden': !loop && isLastSlide }" aria-label="Next slide" @click="next">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    </button>

    <!-- Dot indicators -->
    <div v-if="showDots" class="ch-carousel__dots" role="tablist" aria-label="Slide navigation">
      <button v-for="(slide, index) in slides" :key="index"
        :class="['ch-carousel__dot', { 'ch-carousel__dot--active': index === currentIndex }]"
        :aria-label="'Go to slide ' + (index + 1)" :aria-current="index === currentIndex ? 'true' : 'false'" role="tab"
        @click="goToSlide(index)"></button>
    </div>

    <!-- Thumbnail navigation -->
    <div v-if="showThumbnails" class="ch-carousel__thumbnails">
      <button v-for="(slide, index) in slides" :key="index"
        :class="['ch-carousel__thumbnail', { 'ch-carousel__thumbnail--active': index === currentIndex }]"
        :aria-label="'Go to slide ' + (index + 1)" :aria-current="index === currentIndex ? 'true' : 'false'"
        @click="goToSlide(index)">
        <img v-if="slide.src" :src="slide.src" :alt="slide.alt || ''" class="ch-carousel__thumbnail-image" />
        <span v-else class="ch-carousel__thumbnail-placeholder">{{ index + 1 }}</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
/* ─── Carousel root ───────────────────────────────────────────────────────── */
.ch-carousel {
  position: relative;
  width: 100%;
  overflow: hidden;
}

/* ─── Viewport ────────────────────────────────────────────────────────────── */
.ch-carousel__viewport {
  width: 100%;
  overflow: hidden;
}

/* ─── Track ───────────────────────────────────────────────────────────────── */
.ch-carousel__track {
  display: flex;
  transition: transform var(--ch-duration-slower) var(--ch-ease-out);
  will-change: transform;
}

/* ─── Slide ───────────────────────────────────────────────────────────────── */
.ch-carousel__slide {
  position: relative;
  flex: 0 0 100%;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--ch-color-bg-muted);
}

.ch-carousel__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* ─── Overlay ─────────────────────────────────────────────────────────────── */
.ch-carousel__overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: var(--ch-space-6) var(--ch-space-4);
  background: linear-gradient(to top, rgb(0 0 0 / 0.7) 0%, transparent 100%);
  color: white;
}

.ch-carousel__title {
  font-size: var(--ch-text-lg);
  font-weight: var(--ch-font-semibold);
  margin-bottom: var(--ch-space-1);
}

.ch-carousel__description {
  font-size: var(--ch-text-sm);
  opacity: 0.9;
}

/* ─── Navigation arrows ───────────────────────────────────────────────────── */
.ch-carousel__arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgb(0 0 0 / 0.4);
  border: none;
  padding: var(--ch-space-2);
  color: white;
  cursor: pointer;
  border-radius: var(--ch-radius-none);
  display: flex;
  align-items: center;
  justify-content: center;
  transition:
    background-color var(--ch-duration-fast) var(--ch-ease-out),
    opacity var(--ch-duration-fast) var(--ch-ease-out);
  z-index: var(--ch-z-dropdown);
}

.ch-carousel__arrow:hover:not(:disabled) {
  background: rgb(0 0 0 / 0.6);
}

.ch-carousel__arrow:focus-visible {
  outline: 2px solid white;
  outline-offset: 2px;
}

.ch-carousel__arrow--prev {
  left: var(--ch-space-3);
}

.ch-carousel__arrow--next {
  right: var(--ch-space-3);
}

.ch-carousel__arrow--hidden {
  opacity: 0;
  pointer-events: none;
}

/* ─── Dot indicators ──────────────────────────────────────────────────────── */
.ch-carousel__dots {
  position: absolute;
  bottom: var(--ch-space-4);
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: var(--ch-space-2);
  z-index: var(--ch-z-dropdown);
}

.ch-carousel__dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: 2px solid white;
  background: rgb(0 0 0 / 0.3);
  cursor: pointer;
  padding: 0;
  transition:
    background-color var(--ch-duration-fast) var(--ch-ease-out),
    transform var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-carousel__dot:hover {
  background: rgb(0 0 0 / 0.5);
}

.ch-carousel__dot--active {
  background: white;
  transform: scale(1.2);
}

.ch-carousel__dot:focus-visible {
  outline: 2px solid white;
  outline-offset: 2px;
}

/* ─── Thumbnail navigation ────────────────────────────────────────────────── */
.ch-carousel__thumbnails {
  display: flex;
  gap: var(--ch-space-2);
  padding: var(--ch-space-3);
  background: var(--ch-color-surface);
  overflow-x: auto;
}

.ch-carousel__thumbnail {
  flex: 0 0 auto;
  width: 80px;
  height: 60px;
  border: 2px solid var(--ch-color-border);
  border-radius: var(--ch-radius-none);
  overflow: hidden;
  cursor: pointer;
  padding: 0;
  transition:
    border-color var(--ch-duration-fast) var(--ch-ease-out),
    transform var(--ch-duration-fast) var(--ch-ease-out);
}

.ch-carousel__thumbnail:hover {
  border-color: var(--ch-color-border-strong);
}

.ch-carousel__thumbnail--active {
  border-color: var(--ch-color-primary);
  transform: scale(1.05);
}

.ch-carousel__thumbnail:focus-visible {
  outline: 2px solid var(--ch-color-primary);
  outline-offset: 2px;
}

.ch-carousel__thumbnail-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.ch-carousel__thumbnail-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  background: var(--ch-color-bg-muted);
  color: var(--ch-color-text-muted);
  font-size: var(--ch-text-sm);
  font-weight: var(--ch-font-semibold);
}
</style>