import { useState } from 'react';
import ReadMoreToggle from '@/components/ui/ReadMoreToggle';
import { cn } from '@/lib/cn';
import { renderBold } from '@/lib/richText';

export default function ExpandableQuote({ text, onSky = false }: Readonly<{ text: string; onSky?: boolean }>) {
    const [expanded, setExpanded] = useState(false);
    return (
        <div>
            <p className={cn('whitespace-pre-line font-display text-base italic leading-relaxed', onSky ? 'text-cream' : 'text-ink', !expanded && 'line-clamp-3')}>
                &ldquo;{renderBold(text)}&rdquo;
            </p>
            {text.length > 150 && <ReadMoreToggle expanded={expanded} onToggle={() => setExpanded(!expanded)} />}
        </div>
    );
}
