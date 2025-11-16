import React, { useEffect, useRef, useState } from 'react';

/**
 * Props:
 * - src (string) required
 * - alt (string)
 * - width (number) optional (used for intrinsic/responsive)
 * - height (number) optional
 * - layout: 'intrinsic' | 'responsive' | 'fill' (default 'intrinsic')
 * - objectFit (string) e.g. 'cover' | 'contain'
 * - placeholder: 'blur' | undefined
 * - blurDataURL: small base64/blur image URL (used when placeholder==='blur')
 * - loader: (src, width) => url  (optional) — useful when using Cloudinary/imgix
 * - priority: boolean (preload)
 * - className: string
 */
export default function InertiaImage({
                                         src,
                                         alt = '',
                                         width,
                                         height,
                                         layout = 'intrinsic',
                                         objectFit = 'cover',
                                         placeholder,
                                         blurDataURL,
                                         loader,
                                         priority = false,
                                         className = '',
                                         ...rest
                                     }) {
    const imgRef = useRef(null);
    const [loaded, setLoaded] = useState(false);

    // default loader (no-op)
    const defaultLoader = (srcUrl, w) => srcUrl;

    const resolveUrl = (w) => (loader || defaultLoader)(src, w);

    // generate srcset for 1x, 2x, 3x if width provided
    const buildSrcSet = () => {
        if (!width) return undefined;
        const dprs = [1, 2, 3];
        return dprs
            .map((dpr) => {
                const w = Math.round(width * dpr);
                return `${resolveUrl(w)} ${dpr}x`;
            })
            .join(', ');
    };

    // preload for priority images
    useEffect(() => {
        if (!priority) return;
        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = 'image';
        link.href = resolveUrl(width || undefined);
        document.head.appendChild(link);
        return () => document.head.removeChild(link);
    }, [priority, src, width]);

    // handle onLoad
    const handleLoad = (e) => {
        setLoaded(true);
        if (rest.onLoad) rest.onLoad(e);
    };

    // styles
    const wrapperStyle =
        layout === 'fill'
            ? { position: 'relative', width: '100%', height: '100%' }
            : layout === 'responsive' && width && height
                ? { position: 'relative', paddingBottom: `${(height / width) * 100}%`, overflow: 'hidden' }
                : {};

    const imgStyle =
        layout === 'fill' || layout === 'responsive'
            ? { position: 'absolute', top: 0, left: 0, width: '100%', height: '100%', objectFit }
            : { width: width ? `${width}px` : undefined, height: height ? `${height}px` : undefined, objectFit };

    // src for img tag
    const imgSrc = resolveUrl(width || undefined);
    const srcSet = buildSrcSet();

    return (
        <div className={`inertia-image-wrapper ${className}`} style={wrapperStyle}>
            {/* blur placeholder under the real image */}
            {placeholder === 'blur' && !loaded && blurDataURL && (
                <img
                    src={blurDataURL}
                    alt={alt}
                    aria-hidden
                    style={{
                        ...imgStyle,
                        filter: 'blur(8px)',
                        transform: 'scale(1.02)',
                        transition: 'opacity .3s ease',
                        opacity: loaded ? 0 : 1,
                    }}
                />
            )}

            <img
                ref={imgRef}
                src={imgSrc}
                srcSet={srcSet}
                sizes={width ? `${width}px` : undefined}
                alt={alt}
                loading={priority ? 'eager' : 'lazy'}
                onLoad={handleLoad}
                style={{
                    ...imgStyle,
                    transition: 'opacity .25s ease',
                    opacity: loaded ? 1 : 0,
                    backgroundColor: placeholder === 'blur' && !blurDataURL ? '#f0f0f0' : undefined,
                }}
                {...rest}
            />
        </div>
    );
}
