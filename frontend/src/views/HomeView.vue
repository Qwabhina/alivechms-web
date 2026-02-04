<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
</script>

<template>
  <div class="p-8 space-y-4">
    <Card class="max-w-md mx-auto">
      <CardHeader>
        <CardTitle>AliveCHMS Frontend</CardTitle>
      </CardHeader>
      <CardContent class="space-y-4">
        <p class="text-muted-foreground">
          VueJS project initialized with Shadcn Vue, Pinia, and Tailwind.
        </p>
        <div class="flex gap-2">
          <Badge>Vue 3</Badge>
          <Badge variant="secondary">Vite</Badge>
          <Badge variant="outline">Tailwind</Badge>
        </div>
        <Button class="w-full">Get Started</Button>
      </CardContent>
    </Card>
  </div>
</template>
