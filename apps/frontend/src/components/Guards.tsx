import { Navigate, useLocation } from 'react-router'
import type { ReactNode } from 'react'
import { useAuth } from '../hooks/useAuth'
import { RouteName } from '../enums/RouteName'
import type { PermissionSlug } from '../enums/PermissionSlug'

function Loading() {
  return <div className="state">Loading…</div>
}

/**
 * Sends a signed-out visitor to the login page, remembering where they were
 * headed so they land there afterwards instead of on the home page.
 */
export function RequireAuth({ children }: { children: ReactNode }) {
  const { isAuthenticated, loading } = useAuth()
  const location = useLocation()

  if (loading) {
    return <Loading />
  }

  if (!isAuthenticated) {
    return <Navigate to={RouteName.LOGIN} state={{ from: location.pathname }} replace />
  }

  return <>{children}</>
}

/**
 * Hides a whole area from someone without the permission.
 *
 * This is convenience, not security: the API checks the same permission through
 * a Policy on every request, and that is the check that actually protects data.
 */
export function RequirePermission({
  permission,
  children,
}: {
  permission: PermissionSlug | PermissionSlug[]
  children: ReactNode
}) {
  const { can, loading, isAuthenticated } = useAuth()
  const location = useLocation()

  if (loading) {
    return <Loading />
  }

  if (!isAuthenticated) {
    return <Navigate to={RouteName.LOGIN} state={{ from: location.pathname }} replace />
  }

  if (!can(permission)) {
    return (
      <div className="state">
        <h2>Not available</h2>
        <p>Your account does not have access to this area.</p>
      </div>
    )
  }

  return <>{children}</>
}

/** Keeps a signed-in user away from the login and register pages. */
export function RedirectIfAuthenticated({ children }: { children: ReactNode }) {
  const { isAuthenticated, loading } = useAuth()

  if (loading) {
    return <Loading />
  }

  return isAuthenticated ? <Navigate to={RouteName.MENU} replace /> : <>{children}</>
}
