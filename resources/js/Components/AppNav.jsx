import { router, usePage } from '@inertiajs/react';
import {
    HomeIcon,
    ProductIcon,
    AutomationIcon,
} from '@shopify/polaris-icons';
import { Navigation } from '@shopify/polaris';

export default function AppNav() {
    const { url } = usePage();

    const go = (path) => {
        router.visit(path);
    };

    return (
        <Navigation location={url}>
            <Navigation.Section
                items={[
                    {
                        label: 'Dashboard',
                        icon: HomeIcon,
                        url: '/',
                        selected: url === '/',
                        onClick: () => go('/'),
                    },
                    {
                        label: 'Products',
                        icon: ProductIcon,
                        url: '/products',
                        selected: url.startsWith('/products'),
                        onClick: () => go('/products'),
                    },
                    {
                        label: 'Rules',
                        icon: AutomationIcon,
                        url: '/rules',
                        selected: url.startsWith('/rules'),
                        onClick: () => go('/rules'),
                    },
                ]}
            />
        </Navigation>
    );
}
