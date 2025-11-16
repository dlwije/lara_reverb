import Banner from '@/components/e-commerce/template/banner';
import Navbar from '@/components/e-commerce/template/navBar';
import Footer from '@/components/e-commerce/template/footer';

export default function PublicLayout({ children }) {

    return (
        <>
            <Banner />
            <Navbar />
            {children}
            <Footer />
        </>
    );
}
