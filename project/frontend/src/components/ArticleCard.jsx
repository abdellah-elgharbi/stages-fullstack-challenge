import { useState } from 'react';
import CommentList from './CommentList';

function ArticleCard({ article, onDelete, onCommentsCountChange }) {
  const [showComments, setShowComments] = useState(false);

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';

    const date = new Date(dateString);

    // Format date and time in French locale and Europe/Paris timezone
    const datePart = new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      timeZone: 'Europe/Paris'
    }).format(date);

    const timePart = new Intl.DateTimeFormat('fr-FR', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
      timeZone: 'Europe/Paris'
    }).format(date);

    // extract short timezone name (e.g., CET/CEST)
    const tzPart = new Intl.DateTimeFormat('fr-FR', {
      timeZone: 'Europe/Paris',
      timeZoneName: 'short'
    }).formatToParts(date).find(p => p.type === 'timeZoneName')?.value || '';

    return `${datePart} à ${timePart}${tzPart ? ' (' + tzPart + ')' : ''}`;
  };

  const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000';

  return (
    <div className="card">
      <h3>{article.title}</h3>
      <div style={{ color: '#7f8c8d', fontSize: '0.9em', marginBottom: '0.5rem' }}>
        Par {article.author} • {formatDate(article.created_at)}
      </div>

      {article.image_path && (
        <picture style={{ display: 'block', marginBottom: '1rem' }}>
          <source
            srcSet={`${API_URL}${article.image_path}`}
            type="image/webp"
          />
          <img
            src={`${API_URL}${article.image_path}`}
            alt={article.title}
            loading="lazy"
            width="600"
            height="400"
            style={{
              width: '100%',
              height: 'auto',
              borderRadius: '4px',
              objectFit: 'cover'
            }}
          />
        </picture>
      )}

      <p style={{ marginBottom: '1rem' }}>{article.content}</p>

      <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
        <button
          onClick={() => setShowComments(!showComments)}
          style={{ fontSize: '0.9em' }}
        >
          {showComments ? 'Masquer' : 'Afficher'} commentaires ({article.comments_count || 0})
        </button>

        {onDelete && (
          <button
            onClick={() => onDelete(article.id)}
            style={{
              backgroundColor: '#e74c3c',
              fontSize: '0.9em'
            }}
          >
            Supprimer
          </button>
        )}
      </div>

      {showComments && (
        <div style={{ marginTop: '1rem', borderTop: '1px solid #ecf0f1', paddingTop: '1rem' }}>
          <CommentList
            articleId={article.id}
            onCommentAdded={() => onCommentsCountChange(article.id, +1)}
            onCommentDeleted={() => onCommentsCountChange(article.id, -1)}
          />
        </div>
      )}
    </div>
  );
}

export default ArticleCard;

