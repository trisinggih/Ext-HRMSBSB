import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import api from '../api';

interface CmsSection {
  id: number;
  section_type: string;
  title: string;
  content: string;
  order: number;
}

interface CmsPageData {
  id: number;
  title: string;
  slug: string;
  meta_description: string;
  sections: CmsSection[];
}

export function CmsPage() {
  const { slug } = useParams<{ slug: string }>();
  const [page, setPage] = useState<CmsPageData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchPage = async () => {
      try {
        setLoading(true);
        // Fallback to 'home' if no slug is provided
        const targetSlug = slug || 'home';
        const response = await api.get(`/cms/pages/${targetSlug}`);
        setPage(response.data.data);
        setError(null);
      } catch (err) {
        console.error("Error fetching CMS page", err);
        setError("Failed to load page content.");
      } finally {
        setLoading(false);
      }
    };

    fetchPage();
  }, [slug]);

  if (loading) {
    return <div className="min-h-screen flex items-center justify-center">Loading...</div>;
  }

  if (error || !page) {
    return <div className="min-h-screen flex items-center justify-center text-red-500">{error || 'Page not found'}</div>;
  }

  return (
    <div className="cms-page-container">
      {page.sections && page.sections.sort((a, b) => a.order - b.order).map((section) => (
        <section key={section.id} className="py-12 px-4 max-w-7xl mx-auto">
          {section.title && <h2 className="text-3xl font-bold mb-6 text-center">{section.title}</h2>}
          <div 
            className="prose prose-slate max-w-none"
            dangerouslySetInnerHTML={{ __html: section.content }} 
          />
        </section>
      ))}
    </div>
  );
}
