import { createContext, useCallback, useEffect, useMemo, useState, type ReactNode } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import * as authApi from '../api/auth'
import { getToken, UNAUTHENTICATED_EVENT } from '../lib/api'
import { User } from '../models/User'
import type { PermissionSlug } from '../enums/PermissionSlug'

interface AuthContextValue {
  user: User | null
  /** True until the stored token has been checked against the API. */
  loading: boolean
  isAuthenticated: boolean
  login: (payload: authApi.LoginPayload) => Promise<void>
  register: (payload: authApi.RegisterPayload) => Promise<void>
  logout: () => Promise<void>
  can: (permission: PermissionSlug | PermissionSlug[]) => boolean
}

// eslint-disable-next-line react-refresh/only-export-components
export const AuthContext = createContext<AuthContextValue | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)

  // Derived, not set in an effect: with no stored token there is nothing to
  // verify, so the app is not loading and never has to render a spinner it
  // would immediately replace.
  const [loading, setLoading] = useState(() => getToken() !== null)
  const queryClient = useQueryClient()

  /**
   * A token in storage is a claim, not proof - it may have been revoked from
   * another device or expired. On boot we ask the API who we are, and only a
   * successful answer signs the user in.
   */
  useEffect(() => {
    if (!getToken()) {
      return
    }

    let cancelled = false

    authApi
      .me()
      .then((me) => {
        if (!cancelled) {
          setUser(me)
        }
      })
      .catch(() => {
        // The 401 interceptor has already cleared the token.
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false)
        }
      })

    return () => {
      cancelled = true
    }
  }, [])

  /**
   * Any request rejected as unauthenticated signs the user out here, so an
   * expired token cannot leave the app rendering a signed-in shell it can no
   * longer fill.
   */
  useEffect(() => {
    const signOut = () => {
      setUser(null)
      queryClient.clear()
    }

    window.addEventListener(UNAUTHENTICATED_EVENT, signOut)

    return () => window.removeEventListener(UNAUTHENTICATED_EVENT, signOut)
  }, [queryClient])

  const login = useCallback(
    async (payload: authApi.LoginPayload) => {
      setUser(await authApi.login(payload))
      // Anything cached for the previous visitor belongs to them, not this user.
      queryClient.clear()
    },
    [queryClient],
  )

  const register = useCallback(
    async (payload: authApi.RegisterPayload) => {
      setUser(await authApi.register(payload))
      queryClient.clear()
    },
    [queryClient],
  )

  const logout = useCallback(async () => {
    await authApi.logout()
    setUser(null)
    queryClient.clear()
  }, [queryClient])

  const can = useCallback(
    (permission: PermissionSlug | PermissionSlug[]) => user?.can(permission) ?? false,
    [user],
  )

  const value = useMemo(
    () => ({
      user,
      loading,
      isAuthenticated: user !== null,
      login,
      register,
      logout,
      can,
    }),
    [user, loading, login, register, logout, can],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
