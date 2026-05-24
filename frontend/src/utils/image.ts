export function normalizeProfileImage(src?: string | null): string | undefined {
  if (!src) return undefined
  const s = src.trim()

  try {
    // Absolute URL (with protocol)
    if (s.startsWith('http://') || s.startsWith('https://')) {
      const u = new URL(s)
      const idx = u.pathname.indexOf('uploads')
      if (idx !== -1) return u.pathname.startsWith('/') ? u.pathname : '/' + u.pathname
      return s
    }

    // Protocol-relative //host/path
    if (s.startsWith('//')) {
      const u = new URL(window.location.protocol + s)
      const idx = u.pathname.indexOf('uploads')
      if (idx !== -1) return u.pathname
      return s
    }

    // Already a root-relative path
    if (s.startsWith('/')) return s

    // Contains uploads somewhere (e.g. 'http://host/uploads/...' or 'uploads/members/...')
    const uploadsIdx = s.indexOf('uploads')
    if (uploadsIdx !== -1) return '/' + s.substring(uploadsIdx)

    // Otherwise assume it's a relative path under uploads and prefix slash
    return s.startsWith('/') ? s : '/' + s
  } catch (e) {
    return src
  }
}
