import { useContext } from 'react'
import { AuthContext } from '../context/AuthContext'

/**
 * The only way components read who is signed in and what they may do.
 *
 *     const { user, can } = useAuth()
 *     if (can(PermissionSlug.MANAGE_PRODUCTS)) { ... }
 */
export function useAuth() {
  const context = useContext(AuthContext)

  if (!context) {
    throw new Error('useAuth must be used inside an AuthProvider.')
  }

  return context
}
