import { NavLink, Outlet } from 'react-router'
import { useAuth } from '../hooks/useAuth'
import { useCart } from '../hooks/useCart'
import { RouteName } from '../enums/RouteName'
import { PermissionSlug } from '../enums/PermissionSlug'

function navClass({ isActive }: { isActive: boolean }): string {
  return isActive ? 'header__link header__link--active' : 'header__link'
}

export function Layout() {
  const { user, isAuthenticated, logout, can } = useAuth()

  // Only asked for when signed in - the endpoint needs a token.
  const { data: cart } = useCart(isAuthenticated)

  return (
    <div className="app">
      <header className="header">
        <div className="header__inner">
          <NavLink to={RouteName.MENU} className="header__brand">
            🍕 Food Ordering
          </NavLink>

          <nav className="header__nav">
            {isAuthenticated ? (
              <>
                <NavLink to={RouteName.MENU} className={navClass}>
                  Menu
                </NavLink>

                <NavLink to={RouteName.CART} className={navClass}>
                  Cart
                  {cart && cart.items_count > 0 ? (
                    <span className="header__count">{cart.items_count}</span>
                  ) : null}
                </NavLink>

                <NavLink to={RouteName.ORDERS} className={navClass}>
                  Orders
                </NavLink>

                {can(PermissionSlug.MANAGE_PRODUCTS) || can(PermissionSlug.MANAGE_ORDERS) ? (
                  <NavLink to={RouteName.ADMIN_ORDERS} className={navClass}>
                    Admin
                  </NavLink>
                ) : null}

                <span className="header__link muted small" title={user?.email}>
                  {user?.name}
                </span>

                <button type="button" className="btn btn--sm" onClick={() => void logout()}>
                  Sign out
                </button>
              </>
            ) : (
              <>
                <NavLink to={RouteName.LOGIN} className={navClass}>
                  Sign in
                </NavLink>
                <NavLink to={RouteName.REGISTER} className="btn btn--primary btn--sm">
                  Create account
                </NavLink>
              </>
            )}
          </nav>
        </div>
      </header>

      <main className="app__main">
        <Outlet />
      </main>
    </div>
  )
}
