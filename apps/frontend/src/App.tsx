import { Navigate, Route, Routes } from 'react-router'
import { Layout } from './components/Layout'
import { RedirectIfAuthenticated, RequireAuth, RequirePermission } from './components/Guards'
import { Cart } from './pages/Cart'
import { Checkout } from './pages/Checkout'
import { Login } from './pages/Login'
import { Menu } from './pages/Menu'
import { OrderDetail } from './pages/OrderDetail'
import { Orders } from './pages/Orders'
import { Register } from './pages/Register'
import { AdminCategories } from './pages/admin/AdminCategories'
import { AdminProducts } from './pages/admin/AdminProducts'
import { PermissionSlug } from './enums/PermissionSlug'
import { RouteName } from './enums/RouteName'
import './App.css'

export default function App() {
  return (
    <Routes>
      <Route element={<Layout />}>
        <Route path={RouteName.HOME} element={<Navigate to={RouteName.MENU} replace />} />

        <Route
          path={RouteName.LOGIN}
          element={
            <RedirectIfAuthenticated>
              <Login />
            </RedirectIfAuthenticated>
          }
        />

        <Route
          path={RouteName.REGISTER}
          element={
            <RedirectIfAuthenticated>
              <Register />
            </RedirectIfAuthenticated>
          }
        />

        {/* Customer area -------------------------------------------------- */}
        <Route
          path={RouteName.MENU}
          element={
            <RequireAuth>
              <Menu />
            </RequireAuth>
          }
        />

        <Route
          path={RouteName.CART}
          element={
            <RequireAuth>
              <Cart />
            </RequireAuth>
          }
        />

        <Route
          path={RouteName.CHECKOUT}
          element={
            <RequireAuth>
              <Checkout />
            </RequireAuth>
          }
        />

        <Route
          path={RouteName.ORDERS}
          element={
            <RequireAuth>
              <Orders />
            </RequireAuth>
          }
        />

        <Route
          path={RouteName.ORDER}
          element={
            <RequireAuth>
              <OrderDetail />
            </RequireAuth>
          }
        />

        {/* Admin area ----------------------------------------------------- */}
        <Route path={RouteName.ADMIN} element={<Navigate to={RouteName.ADMIN_PRODUCTS} replace />} />

        <Route
          path={RouteName.ADMIN_PRODUCTS}
          element={
            <RequirePermission permission={PermissionSlug.MANAGE_PRODUCTS}>
              <AdminProducts />
            </RequirePermission>
          }
        />

        <Route
          path={RouteName.ADMIN_CATEGORIES}
          element={
            <RequirePermission permission={PermissionSlug.MANAGE_PRODUCT_CATEGORIES}>
              <AdminCategories />
            </RequirePermission>
          }
        />

        <Route
          path="*"
          element={
            <div className="state">
              <h2>Page not found</h2>
            </div>
          }
        />
      </Route>
    </Routes>
  )
}
