<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ChPageHeader, ChStatCard, ChButton, ChSpinner, ChAvatar } from '@/design-system'
import { normalizeProfileImage } from '@/utils/image'
import { Users, UserCheck, UserMinus, Home } from 'lucide-vue-next'
import ChChart from '@/design-system/components/data/ChChart.vue'
import type { ChartOptions } from 'chart.js'
import { memberService } from '@/services/member.service'
import { familyService } from '@/services/family.service'
import { useToast } from '@/design-system'

const router = useRouter()
const toast = useToast()

const isLoading = ref(true)
const stats = ref<any>(null)
const familiesTotal = ref<number | null>(null)
const recent = ref<Array<any>>([])
const upcomingBirthdays = ref<Array<any>>([])
const upcomingAnniversaries = ref<Array<any>>([])
const birthdayPage = ref(1)
const anniversaryPage = ref(1)
const birthdaysTotal = ref<number | null>(null)
const anniversariesTotal = ref<number | null>(null)

function goToDirectory() {
  router.push('/members/directory')
}
function goToCreate() {
  router.push('/members/create')
}

/** Compute upcoming occurrences within next `days` */
function upcomingFromMembers(members: any[], days = 30) {
  const now = new Date()
  const end = new Date(now)
  end.setDate(now.getDate() + days)

  const birthdays: any[] = []
  const anniversaries: any[] = []

  for (const m of members) {
    const dob = m.MbrDateOfBirth
    const reg = m.MbrRegistrationDate

    if (dob) {
      const next = nextOccurrence(dob, now)
      if (next >= now && next <= end) {
        birthdays.push({ member: m, date: next })
      }
    }

    if (reg) {
      const nextAnn = nextOccurrence(reg, now)
      if (nextAnn >= now && nextAnn <= end) {
        anniversaries.push({ member: m, date: nextAnn })
      }
    }
  }

  // sort by date asc and return up to 6 each
  birthdays.sort((a, b) => +a.date - +b.date)
  anniversaries.sort((a, b) => +a.date - +b.date)
  return { birthdays: birthdays.slice(0, 6), anniversaries: anniversaries.slice(0, 6) }
}

function nextOccurrence(isoDate: string, reference: Date) {
  // isoDate like 1985-04-12 or datetime; we only need month/day
  const d = new Date(isoDate)
  if (isNaN(d.getTime())) return new Date(8640000000000000)
  const year = reference.getFullYear()
  const occ = new Date(year, d.getMonth(), d.getDate())
  // If already passed this year, use next year
  if (occ < reference) occ.setFullYear(year + 1)
  return occ
}

onMounted(async () => {
  try {
    isLoading.value = true
    const [sRes, fRes, rRes, upRes] = await Promise.all([
      memberService.getStats(),
      familyService.list(1, 1),
      memberService.getRecent(),
      memberService.upcoming(30, 10),
    ])

    stats.value = sRes.data ?? null
    familiesTotal.value = (fRes.data?.pagination?.total) as number | null
    console.log(fRes.data?.pagination)
    recent.value = rRes.data?.data ?? []

    const up = upRes.data ?? { birthdays: [], anniversaries: [] }
    upcomingBirthdays.value = up.birthdays ?? []
    upcomingAnniversaries.value = up.anniversaries ?? []
    birthdaysTotal.value = up.birthdays_total ?? null
    anniversariesTotal.value = up.anniversaries_total ?? null

    // Prepare chart arrays
    // stats.value contains gender_distribution and age_distribution
  } catch (err: unknown) {
    const msg = err instanceof Error ? err.message : 'Failed to load members dashboard data.'
    toast.error(msg, { title: 'Members Dashboard' })
  } finally {
    isLoading.value = false
  }
})

async function loadMore(type: 'birthdays' | 'anniversaries') {
  try {
    if (type === 'birthdays') {
      birthdayPage.value += 1
      const res = await memberService.upcoming(30, 10, birthdayPage.value, 'birthdays')
      const items = res.data?.birthdays ?? []
      upcomingBirthdays.value = upcomingBirthdays.value.concat(items)
      birthdaysTotal.value = res.data?.birthdays_total ?? birthdaysTotal.value
    } else {
      anniversaryPage.value += 1
      const res = await memberService.upcoming(30, 10, anniversaryPage.value, 'anniversaries')
      const items = res.data?.anniversaries ?? []
      upcomingAnniversaries.value = upcomingAnniversaries.value.concat(items)
      anniversariesTotal.value = res.data?.anniversaries_total ?? anniversariesTotal.value
    }
  } catch (err: unknown) {
    const msg = err instanceof Error ? err.message : 'Failed to load more items.'
    toast.error(msg)
  }
}

const totalMembers = computed(() => stats.value?.total_members ?? '—')
const totalFamilies = computed(() => familiesTotal.value ?? '—')
const activeMembers = computed(() => stats.value?.active_members ?? '—')
const inactiveMembers = computed(() => stats.value?.inactive_members ?? '—')

const genderLabels = computed(() => (stats.value?.gender_distribution ?? []).map((g: any) => g.gender))
const genderValues = computed(() => (stats.value?.gender_distribution ?? []).map((g: any) => g.count))
const ageLabels = computed(() => (stats.value?.age_distribution ?? []).map((a: any) => a.group))
const ageValues = computed(() => (stats.value?.age_distribution ?? []).map((a: any) => a.count))

const chartGenderData = computed(() => ({
  labels: genderLabels.value,
  datasets: [
    { label: 'Members', data: (stats.value?.gender_distribution ?? []).map((g: any) => Number(g.count) || 0) },
  ],
}))

const chartAgeData = computed(() => ({
  labels: ageLabels.value,
  datasets: [
    { label: 'Members', data: (stats.value?.age_distribution ?? []).map((a: any) => Number(a.count) || 0) },
  ],
}))

const chartOptionsBase = {
  plugins: { legend: { position: 'top' as const } },
  scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { ticks: { autoSkip: false } } },
} as unknown as ChartOptions

const chartGenderOptions = computed<ChartOptions>(() => ({ ...chartOptionsBase }))
const chartAgeOptions = computed<ChartOptions>(() => ({ ...chartOptionsBase }))

</script>

<template>
  <div class="view">
    <ChPageHeader title="Members" subtitle="Overview and quick actions for the members module." titleTag="h2">
      <template #actions>
        <ChButton @click="goToCreate">Create Member</ChButton>
        <ChButton variant="ghost" @click="goToDirectory">View Directory</ChButton>
      </template>
    </ChPageHeader>

    <div class="stat-grid">
      <ChStatCard label="Total Members" :value="totalMembers" variant="primary">
        <template #icon>
          <Users :size="20" />
        </template>
        </ChStatCard>

      <ChStatCard label="Active Members" :value="activeMembers" variant="success">
        <template #icon>
          <UserCheck :size="20" />
        </template>
      </ChStatCard>

      <ChStatCard label="Inactive Members" :value="inactiveMembers" variant="danger">
        <template #icon>
          <UserMinus :size="20" />
        </template>
      </ChStatCard>

      <ChStatCard label="Families" :value="totalFamilies" variant="warning">
        <template #icon>
          <Home :size="20" />
        </template>
      </ChStatCard>
    </div>

    <div class="charts-grid">
      <ChCard>
        <template #header>
          <span class="card-title">Gender Distribution (Active)</span>
        </template>
        <div v-if="isLoading" class="card-spinner"><ChSpinner /></div>
        <div v-else>
          <ChChart type="bar" :data="chartGenderData" :options="chartGenderOptions" label="Gender distribution" :height="220" />
        </div>
      </ChCard>

      <ChCard>
        <template #header>
          <span class="card-title">Age Distribution (Active)</span>
        </template>
        <div v-if="isLoading" class="card-spinner"><ChSpinner /></div>
        <div v-else>
          <ChChart type="bar" :data="chartAgeData" :options="chartAgeOptions" label="Age distribution" :height="220" />
        </div>
      </ChCard>
    </div>

    <div class="bottom-grid">
      <ChCard>
        <template #header>
          <span class="card-title">Upcoming Birthdays</span>
        </template>
        <div v-if="isLoading" class="card-spinner"><ChSpinner /></div>
        <ul v-else-if="upcomingBirthdays.length">
          <li v-for="b in upcomingBirthdays" :key="b.member.MbrID">
            <ChAvatar :name="`${b.member.MbrFirstName} ${b.member.MbrFamilyName}`" :src="normalizeProfileImage(b.member.MbrProfilePicture)" size="sm" />
            <strong>{{ b.member.MbrFirstName }} {{ b.member.MbrFamilyName }}</strong>
            — {{ new Date(b.date).toLocaleDateString() }}
          </li>
          <li v-if="(birthdaysTotal ?? 0) > upcomingBirthdays.length" class="view-more-row">
            <ChButton variant="ghost" @click="loadMore('birthdays')">View more</ChButton>
          </li>
        </ul>
        <div v-else class="empty">No upcoming birthdays.</div>
      </ChCard>

      <ChCard>
        <template #header>
          <span class="card-title">Upcoming Anniversaries</span>
        </template>
        <div v-if="isLoading" class="card-spinner"><ChSpinner /></div>
        <ul v-else-if="upcomingAnniversaries.length">
          <li v-for="a in upcomingAnniversaries" :key="a.member.MbrID">
            <ChAvatar :name="`${a.member.MbrFirstName} ${a.member.MbrFamilyName}`" :src="normalizeProfileImage(a.member.MbrProfilePicture)" size="sm" />
            <strong>{{ a.member.MbrFirstName }} {{ a.member.MbrFamilyName }}</strong>
            — {{ new Date(a.date).toLocaleDateString() }}
          </li>
          <li v-if="(anniversariesTotal ?? 0) > upcomingAnniversaries.length" class="view-more-row">
            <ChButton variant="ghost" @click="loadMore('anniversaries')">View more</ChButton>
          </li>
        </ul>
        <div v-else class="empty">No upcoming anniversaries.</div>
      </ChCard>

      <ChCard>
        <template #header>
          <span class="card-title">Recent Registrations</span>
        </template>
        <div v-if="isLoading" class="card-spinner"><ChSpinner /></div>
        <ul v-else>
          <li v-for="m in recent" :key="m.MbrID" class="recent-row">
            <ChAvatar :name="`${m.MbrFirstName} ${m.MbrFamilyName}`" :src="normalizeProfileImage(m.MbrProfilePicture)" size="sm" />
            <div class="recent-row__info">
              <span class="recent-row__name">{{ m.MbrFirstName }} {{ m.MbrFamilyName }}</span>
              <span class="recent-row__date">Joined {{ new Date(m.MbrRegistrationDate).toLocaleDateString() }}</span>
            </div>
            <ChButton variant="ghost" size="sm" @click="router.push(`/members/${m.MbrID}`)">View</ChButton>
          </li>
        </ul>
      </ChCard>
    </div>
  </div>
</template>

<style scoped>
.stat-grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap:12px; margin-bottom:16px }
.bottom-grid { display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px }
.recent-row { display:flex; align-items:center; gap:8px; padding:8px 0 }
.recent-row__info { flex:1 }
.recent-row__name { font-weight:600 }
.recent-row__date { display:block; color:var(--ch-color-text-muted); font-size:0.9rem }
.stat-value { font-size:1.6rem; font-weight:700 }
.card-spinner { padding:16px; display:flex; justify-content:center }
.empty { padding:12px; color:var(--ch-color-text-muted) }
  .view { padding: 16px 12px }

  .charts-grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(280px,1fr)); gap:16px; margin-bottom:16px }
  .bottom-grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(240px,1fr)); gap:12px }
  .view-more-row { display:flex; justify-content:center; padding:8px 0 }
  .recent-row { display:flex; align-items:center; gap:8px; padding:8px 0 }
  .recent-row__info { flex:1 }
  .recent-row__name { font-weight:600 }
  .recent-row__date { display:block; color:var(--ch-color-text-muted); font-size:0.9rem }
  .stat-value { font-size:1.6rem; font-weight:700 }
  .card-spinner { padding:16px; display:flex; justify-content:center }
  .empty { padding:12px; color:var(--ch-color-text-muted) }
</style>
